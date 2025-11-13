<?php

namespace App\Controller\Api;

use App\Entity\QuickBooking;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

#[Route('/api')]
class QuickBookingController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private MailerInterface $mailer
    ) {}

    #[Route('/quick-booking', name: 'api_quick_booking', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true);

            // Validation
            if (empty($data['name']) || empty($data['phone']) || empty($data['zip'])) {
                return $this->json(['success' => false, 'error' => 'Tous les champs sont obligatoires'], 400);
            }

            // Créer l'entité QuickBooking
            $booking = new QuickBooking();
            $booking->setName($data['name']);
            $booking->setPhone($data['phone']);
            $booking->setZip($data['zip']);
            $booking->setService($data['service'] ?? 'reparation');
            $booking->setCreatedAt(new \DateTimeImmutable());

            $this->entityManager->persist($booking);
            $this->entityManager->flush();

            // Envoyer email à l'admin
            $email = (new Email())
                ->from('noreply@recyclum.fr')
                ->to('contact@recyclum.fr')
                ->subject('Nouveau rappel demandé - ' . $data['service'])
                ->html(sprintf(
                    '<h2>Nouvelle demande de rappel</h2>
                    <p><strong>Nom :</strong> %s</p>
                    <p><strong>Téléphone :</strong> <a href="tel:%s">%s</a></p>
                    <p><strong>Code postal :</strong> %s</p>
                    <p><strong>Service :</strong> %s</p>
                    <p><strong>Date :</strong> %s</p>',
                    htmlspecialchars($data['name']),
                    htmlspecialchars($data['phone']),
                    htmlspecialchars($data['phone']),
                    htmlspecialchars($data['zip']),
                    htmlspecialchars($data['service']),
                    (new \DateTime())->format('d/m/Y H:i')
                ));

            $this->mailer->send($email);

            return $this->json([
                'success' => true,
                'message' => 'Demande enregistrée'
            ], 201);

        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'error' => 'Une erreur est survenue'
            ], 500);
        }
    }
}
