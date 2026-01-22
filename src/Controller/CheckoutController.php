<?php

namespace App\Controller;

use App\Service\CartService;
use App\Service\CheckoutService;
use App\Service\OrderService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/commande', name: 'checkout_')]
class CheckoutController extends AbstractController
{
    public function __construct(
        private CartService $cartService,
        private CheckoutService $checkoutService,
        private OrderService $orderService,
        private EntityManagerInterface $em  // ← AJOUTER CETTE LIGNE
    ) {}

    /**
     * PAGE D'ENTRÉE : Choix invité ou connexion
     */
    #[Route('', name: 'index', methods: ['GET'])]
    public function index(): Response
    {
        $cart = $this->cartService->getCurrentCart();

        // Vérifier que le panier est valide
        $errors = $this->checkoutService->validateCart($cart);
        if (!empty($errors)) {
            foreach ($errors as $error) {
                $this->addFlash('error', $error);
            }
            return $this->redirectToRoute('cart_index');
        }

        // Si déjà connecté, aller directement à la livraison
        if ($this->getUser()) {
            return $this->redirectToRoute('checkout_delivery');
        }

        return $this->render('checkout/index.html.twig', [
            'cart' => $cart,
        ]);
    }

    /**
     * ÉTAPE 1 : Informations de livraison
     */
    #[Route('/livraison', name: 'delivery', methods: ['GET', 'POST'])]
    public function delivery(Request $request): Response
    {
        $cart = $this->cartService->getCurrentCart();

        // Vérifier que le panier est valide
        $errors = $this->checkoutService->validateCart($cart);
        if (!empty($errors)) {
            foreach ($errors as $error) {
                $this->addFlash('error', $error);
            }
            return $this->redirectToRoute('cart_index');
        }

        // Récupérer les données existantes ou initialiser
        $deliveryData = $this->checkoutService->getDeliveryData() ?? [
            'email' => $this->getUser()?->getEmail() ?? '',
            'firstName' => $this->getUser()?->getFirstName() ?? '',
            'lastName' => $this->getUser()?->getLastName() ?? '',
            'street' => '',
            'streetComplement' => '',
            'postalCode' => '',
            'city' => '',
            'phone' => '',
            'instructions' => '',
            'deliveryType' => 'simple',
        ];

        $formErrors = [];

        if ($request->isMethod('POST')) {
            $deliveryData = [
                'firstName' => trim($request->request->get('firstName', '')),
                'lastName' => trim($request->request->get('lastName', '')),
                'street' => trim($request->request->get('street', '')),
                'streetComplement' => trim($request->request->get('streetComplement', '')),
                'postalCode' => trim($request->request->get('postalCode', '')),
                'city' => trim($request->request->get('city', '')),
                'phone' => trim($request->request->get('phone', '')),
                'instructions' => trim($request->request->get('instructions', '')),
                'deliveryType' => $request->request->get('deliveryType', 'simple'),
            ];

            // Only include email for guest users (not logged in)
            if (!$this->getUser()) {
                $deliveryData['email'] = trim($request->request->get('email', ''));
            } else {
                // Use the logged-in user's email
                $deliveryData['email'] = $this->getUser()->getEmail();
            }

            // Valider les données
            $formErrors = $this->checkoutService->validateDeliveryAddress($deliveryData);

            if (empty($formErrors)) {
                // Sauvegarder en session
                $this->checkoutService->saveDeliveryData($deliveryData);

                // Rediriger vers l'étape 2
                return $this->redirectToRoute('checkout_summary');
            }
        }

        return $this->render('checkout/delivery.html.twig', [
            'cart' => $cart,
            'deliveryData' => $deliveryData,
            'errors' => $formErrors,
            'currentStep' => 1,
            'isGuest' => !$this->getUser(),
        ]);
    }

    /**
     * ÉTAPE 2 : Récapitulatif et validation
     */
    #[Route('/recapitulatif', name: 'summary', methods: ['GET', 'POST'])]
    public function summary(Request $request): Response
    {
        $cart = $this->cartService->getCurrentCart();
        $deliveryData = $this->checkoutService->getDeliveryData();

        // Vérifier que l'étape 1 est complète
        if (!$deliveryData) {
            $this->addFlash('warning', 'Veuillez d\'abord renseigner votre adresse de livraison');
            return $this->redirectToRoute('checkout_delivery');
        }

        // Vérifier le panier
        $errors = $this->checkoutService->validateCart($cart);
        if (!empty($errors)) {
            foreach ($errors as $error) {
                $this->addFlash('error', $error);
            }
            return $this->redirectToRoute('cart_index');
        }

        $deliveryType = $deliveryData['deliveryType'] ?? 'simple';
        $summary = $this->checkoutService->getSummary($cart, $deliveryType);

        if ($request->isMethod('POST')) {
            // Valider le token CSRF
            $token = $request->request->get('_token');
            if (!$this->isCsrfTokenValid('checkout_summary', $token)) {
                $this->addFlash('error', 'Token de sécurité invalide');
                return $this->redirectToRoute('checkout_summary');
            }

            // Créer la commande
            try {
                $order = $this->orderService->createOrderFromCart(
                    $cart,
                    $this->getUser(),
                    $deliveryData,
                    $deliveryType
                );

                // Sauvegarder l'ID de la commande
                $this->checkoutService->saveOrderId($order->getId());

                // Rediriger vers le paiement
                return $this->redirectToRoute('checkout_payment');

            } catch (\Exception $e) {
                $this->addFlash('error', 'Erreur lors de la création de la commande : ' . $e->getMessage());
                return $this->redirectToRoute('checkout_summary');
            }
        }

        return $this->render('checkout/summary.html.twig', [
            'cart' => $cart,
            'deliveryData' => $deliveryData,
            'summary' => $summary,
            'currentStep' => 2,
            'isGuest' => !$this->getUser(),
        ]);
    }

    /**
     * ÉTAPE 3 : Paiement
     */
    #[Route('/paiement', name: 'payment', methods: ['GET'])]
    public function payment(): Response
    {
        $orderId = $this->checkoutService->getOrderId();

        if (!$orderId) {
            $this->addFlash('warning', 'Aucune commande en cours');
            return $this->redirectToRoute('checkout_delivery');
        }

        $orderRepository = $this->em->getRepository(\App\Entity\Order::class);
        $order = $orderRepository->find($orderId);

        if (!$order) {
            $this->addFlash('error', 'Commande introuvable');
            return $this->redirectToRoute('checkout_delivery');
        }

        // Vérifier que la commande appartient à l'utilisateur (si connecté)
        if ($this->getUser() && !$this->orderService->belongsToUser($order, $this->getUser())) {
            throw $this->createAccessDeniedException();
        }

        return $this->render('checkout/payment.html.twig', [
            'order' => $order,
            'currentStep' => 3,
            'stripe_public_key' => $this->getParameter('stripe_public_key'),
        ]);
    }

    /**
     * Confirmation de commande
     */
    #[Route('/confirmation/{orderNumber}', name: 'confirmation', methods: ['GET'])]
    public function confirmation(string $orderNumber): Response
    {
        $order = $this->orderService->getOrderByNumber($orderNumber);

        if (!$order) {
            throw $this->createNotFoundException('Commande introuvable');
        }

        // Vérifier que la commande appartient à l'utilisateur (si connecté)
        if ($this->getUser() && !$this->orderService->belongsToUser($order, $this->getUser())) {
            throw $this->createAccessDeniedException();
        }

        // Vider le panier
        $this->cartService->clear();

        // Nettoyer la session checkout
        $this->checkoutService->clear();

        return $this->render('checkout/confirmation.html.twig', [
            'order' => $order,
        ]);
    }

    /**
     * Reprendre le paiement d'une commande en attente
     */
    #[Route('/reprendre/{orderNumber}', name: 'resume', methods: ['GET'])]
    public function resume(string $orderNumber): Response
    {
        $order = $this->orderService->getOrderByNumber($orderNumber);

        if (!$order) {
            throw $this->createNotFoundException('Commande introuvable');
        }

        // Vérifier que la commande appartient à l'utilisateur
        if (!$this->getUser() || !$this->orderService->belongsToUser($order, $this->getUser())) {
            throw $this->createAccessDeniedException('Vous n\'avez pas accès à cette commande');
        }

        // Vérifier que la commande est en attente de paiement
        if ($order->getStatus() !== \App\Enum\OrderStatus::PENDING) {
            $this->addFlash('warning', 'Cette commande ne peut plus être payée');
            return $this->redirectToRoute('account_order_show', ['orderNumber' => $orderNumber]);
        }

        // Sauvegarder l'ID de la commande en session pour le checkout
        $this->checkoutService->saveOrderId($order->getId());

        // Rediriger vers la page de paiement
        return $this->redirectToRoute('checkout_payment');
    }

    /**
     * Annuler le checkout
     */
    #[Route('/annuler', name: 'cancel', methods: ['POST'])]
    public function cancel(Request $request): Response
    {
        $token = $request->request->get('_token');
        if (!$this->isCsrfTokenValid('checkout_cancel', $token)) {
            $this->addFlash('error', 'Token de sécurité invalide');
            return $this->redirectToRoute('cart_index');
        }

        // Annuler la commande en cours si elle existe
        $orderId = $this->checkoutService->getOrderId();
        if ($orderId) {
            $orderRepository = $this->em->getRepository(\App\Entity\Order::class);
            $order = $orderRepository->find($orderId);
            if ($order && $this->orderService->canBeCancelled($order)) {
                $this->orderService->cancelOrder($order, 'Annulée par l\'utilisateur');
            }
        }

        // Nettoyer la session
        $this->checkoutService->clear();

        $this->addFlash('info', 'Commande annulée');
        return $this->redirectToRoute('cart_index');
    }
}
