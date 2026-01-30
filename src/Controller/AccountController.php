<?php

namespace App\Controller;

use App\Form\ProfileFormType;
use App\Form\ChangePasswordFormType;
use App\Repository\ContactMessageRepository;
use App\Repository\OrderRepository;
use App\Service\TicketService;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/mon-compte')]
#[IsGranted('ROLE_USER')]
class AccountController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $em,
        private OrderRepository $orderRepository,
        private ContactMessageRepository $contactMessageRepository,
        private TicketService $ticketService,
    ) {
    }

    #[Route('', name: 'account_dashboard', methods: ['GET'])]
    public function dashboard(): Response
    {
        $user = $this->getUser();

        // Get recent orders (last 3)
        $recentOrders = $this->orderRepository->findBy(
            ['user' => $user],
            ['createdAt' => 'DESC'],
            3
        );

        // Get order stats
        $totalOrders = $this->orderRepository->countByUser($user);
        $activeOrders = $this->orderRepository->countActiveByUser($user);

        return $this->render('account/dashboard.html.twig', [
            'user' => $user,
            'recentOrders' => $recentOrders,
            'totalOrders' => $totalOrders,
            'activeOrders' => $activeOrders,
        ]);
    }

    #[Route('/profil', name: 'account_profile', methods: ['GET', 'POST'])]
    public function profile(Request $request): Response
    {
        $user = $this->getUser();

        $form = $this->createForm(ProfileFormType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->em->flush();

            $this->addFlash('success', 'Vos informations ont été mises à jour.');

            return $this->redirectToRoute('account_profile');
        }

        return $this->render('account/profile.html.twig', [
            'user' => $user,
            'form' => $form,
        ]);
    }

    #[Route('/securite', name: 'account_security', methods: ['GET', 'POST'])]
    public function security(Request $request, UserPasswordHasherInterface $passwordHasher): Response
    {
        $user = $this->getUser();

        $form = $this->createForm(ChangePasswordFormType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $currentPassword = $form->get('currentPassword')->getData();
            $newPassword = $form->get('newPassword')->getData();

            // Verify current password
            if (!$passwordHasher->isPasswordValid($user, $currentPassword)) {
                $this->addFlash('error', 'Le mot de passe actuel est incorrect.');
                return $this->redirectToRoute('account_security');
            }

            // Hash and set new password
            $hashedPassword = $passwordHasher->hashPassword($user, $newPassword);
            $user->setPassword($hashedPassword);

            $this->em->flush();

            $this->addFlash('success', 'Votre mot de passe a été modifié.');

            return $this->redirectToRoute('account_security');
        }

        return $this->render('account/security.html.twig', [
            'user' => $user,
            'form' => $form,
        ]);
    }

    #[Route('/demandes', name: 'account_tickets', methods: ['GET'])]
    public function tickets(): Response
    {
        $user = $this->getUser();

        $tickets = $this->contactMessageRepository->findBy(
            ['user' => $user],
            ['createdAt' => 'DESC']
        );

        return $this->render('account/tickets/index.html.twig', [
            'user' => $user,
            'tickets' => $tickets,
        ]);
    }

    #[Route('/demandes/{id}', name: 'account_ticket_show', methods: ['GET'])]
    public function ticketShow(int $id): Response
    {
        $user = $this->getUser();

        $ticket = $this->contactMessageRepository->find($id);

        if (!$ticket || $ticket->getUser() !== $user) {
            throw $this->createNotFoundException('Demande introuvable.');
        }

        return $this->render('account/tickets/show.html.twig', [
            'user' => $user,
            'message' => $ticket,
        ]);
    }

    #[Route('/demandes/{id}/repondre', name: 'account_ticket_reply', methods: ['POST'])]
    public function ticketReply(int $id, Request $request): Response
    {
        $user = $this->getUser();
        $ticket = $this->contactMessageRepository->find($id);

        // Vérifier que le ticket existe et appartient à l'utilisateur
        if (!$ticket || !$this->ticketService->ticketBelongsToUser($ticket, $user)) {
            throw $this->createNotFoundException('Demande introuvable.');
        }

        // Vérifier le token CSRF
        $csrfToken = $request->request->get('_token');
        if (!$this->isCsrfTokenValid('ticket_reply_' . $id, $csrfToken)) {
            $this->addFlash('error', 'Token de sécurité invalide. Veuillez réessayer.');
            return $this->redirectToRoute('account_ticket_show', ['id' => $id]);
        }

        // Vérifier que le ticket peut recevoir des réponses
        if (!$this->ticketService->canReply($ticket)) {
            $this->addFlash('error', 'Ce ticket est fermé. Vous ne pouvez plus y répondre.');
            return $this->redirectToRoute('account_ticket_show', ['id' => $id]);
        }

        // Valider le contenu
        $content = $request->request->get('content', '');
        $validationError = $this->ticketService->validateReplyContent($content);
        if ($validationError) {
            $this->addFlash('error', $validationError);
            return $this->redirectToRoute('account_ticket_show', ['id' => $id]);
        }

        // Créer la réponse via le service
        $uploadedFiles = $request->files->get('attachments', []) ?? [];
        $reply = $this->ticketService->addClientReply($ticket, $content, $uploadedFiles);

        // Notifier l'admin
        $adminViewUrl = $this->generateUrl('admin_contact_show', [
            'id' => $ticket->getId()
        ], UrlGeneratorInterface::ABSOLUTE_URL);
        $this->ticketService->notifyAdminOfClientReply($ticket, $reply, $adminViewUrl);

        $this->addFlash('success', 'Votre réponse a été envoyée. Notre équipe vous répondra dans les plus brefs délais.');

        return $this->redirectToRoute('account_ticket_show', ['id' => $id]);
    }
}
