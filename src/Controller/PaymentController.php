<?php

namespace App\Controller;

use App\Entity\Order;
use App\Entity\Payment;
use App\Enum\OrderStatus;
use App\Enum\PaymentStatus;
use App\Service\OrderService;
use App\Service\PaymentService;
use App\Service\EmailService;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Stripe\Stripe;
use Stripe\PaymentIntent;

#[Route('/checkout', name: 'checkout_')]
class PaymentController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $em,
        private OrderService $orderService,
        private PaymentService $paymentService,
        private EmailService $emailService,
        private UrlGeneratorInterface $urlGenerator,
        private LoggerInterface $logger
    ) {}

    /**
     * Créer un Payment Intent Stripe
     */
    #[Route('/create-payment-intent', name: 'create_payment_intent', methods: ['POST'])]
    public function createPaymentIntent(Request $request): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true);
            $orderId = $data['orderId'] ?? null;

            if (!$orderId) {
                return new JsonResponse(['error' => 'Order ID manquant'], 400);
            }

            $order = $this->em->getRepository(Order::class)->find($orderId);

            if (!$order) {
                return new JsonResponse(['error' => 'Commande introuvable'], 404);
            }

            // Vérifier que la commande appartient à l'utilisateur (si connecté)
            if ($this->getUser() && !$this->orderService->belongsToUser($order, $this->getUser())) {
                return new JsonResponse(['error' => 'Accès refusé'], 403);
            }

            // Configurer Stripe
            Stripe::setApiKey($this->getParameter('stripe_secret_key'));

            // Créer le Payment Intent
            $paymentIntent = PaymentIntent::create([
                'amount' => $order->getTotal(), // Montant en centimes
                'currency' => 'eur',
                'automatic_payment_methods' => [
                    'enabled' => true,
                ],
                'metadata' => [
                    'order_id' => $order->getId(),
                    'order_number' => $order->getOrderNumber(),
                ],
                'description' => 'Commande ' . $order->getOrderNumber(),
            ]);

            // Créer l'entité Payment
            $payment = $this->paymentService->createPayment(
                $order,
                'stripe',
                $paymentIntent->id,
                $paymentIntent->id
            );

            return new JsonResponse([
                'clientSecret' => $paymentIntent->client_secret,
                'paymentIntentId' => $paymentIntent->id,
            ]);

        } catch (\Exception $e) {
            return new JsonResponse([
                'error' => 'Erreur lors de la création du paiement : ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Confirmer le paiement après succès Stripe
     */
    #[Route('/confirm-payment', name: 'confirm_payment', methods: ['POST'])]
    public function confirmPayment(Request $request): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true);
            $paymentIntentId = $data['paymentIntentId'] ?? null;

            if (!$paymentIntentId) {
                return new JsonResponse(['error' => 'Payment Intent ID manquant'], 400);
            }

            // Trouver le paiement
            $payment = $this->em->getRepository(Payment::class)
                ->findOneBy(['paymentIntentId' => $paymentIntentId]);

            if (!$payment) {
                return new JsonResponse(['error' => 'Paiement introuvable'], 404);
            }

            $order = $payment->getOrder();

            // Vérifier le statut du Payment Intent auprès de Stripe
            Stripe::setApiKey($this->getParameter('stripe_secret_key'));
            $paymentIntent = PaymentIntent::retrieve($paymentIntentId);

            if ($paymentIntent->status === 'succeeded') {
                // Marquer le paiement comme complété
                $this->paymentService->markAsCompleted($payment);

                // Mettre à jour la commande
                $this->orderService->markAsPaid($order);

                // Envoyer l'email de confirmation
                $this->sendOrderConfirmationEmail($order);

                return new JsonResponse([
                    'success' => true,
                    'orderNumber' => $order->getOrderNumber(),
                    'redirectUrl' => $this->generateUrl('checkout_confirmation', [
                        'orderNumber' => $order->getOrderNumber()
                    ]),
                ]);
            }

            return new JsonResponse([
                'error' => 'Le paiement n\'a pas été validé par Stripe'
            ], 400);

        } catch (\Exception $e) {
            return new JsonResponse([
                'error' => 'Erreur lors de la confirmation : ' . $e->getMessage()
            ], 500);
        }
    }

    private function sendOrderConfirmationEmail(Order $order): void
    {
        try {
            $this->logger->info('=== DÉBUT ENVOI EMAIL CONFIRMATION COMMANDE ===', [
                'order_number' => $order->getOrderNumber(),
                'order_id' => $order->getId(),
            ]);

            // Déterminer l'email du destinataire avec fallback
            $recipientEmail = $order->getCustomerEmail();

            // Fallback: si customerEmail est vide, utiliser l'email de l'utilisateur connecté
            if (!$recipientEmail && $order->getUser()) {
                $recipientEmail = $order->getUser()->getEmail();
                $this->logger->info('Fallback vers email utilisateur', [
                    'user_email' => $recipientEmail
                ]);
            }

            $this->logger->info('Email destinataire déterminé', [
                'recipient_email' => $recipientEmail
            ]);

            if (!$recipientEmail) {
                $this->logger->error('ERREUR: Email client introuvable pour la commande', [
                    'order_number' => $order->getOrderNumber(),
                    'has_user' => $order->getUser() !== null,
                    'customer_email_field' => $order->getCustomerEmail(),
                ]);
                throw new \Exception('Email client introuvable');
            }

            $recipientName = $order->getDeliveryFirstName() . ' ' . $order->getDeliveryLastName();

            // Générer le lien de suivi pour les invités
            $trackingUrl = null;
            if (!$order->getUser() && $order->getTrackingToken()) {
                $trackingUrl = $this->urlGenerator->generate('order_tracking', [
                    'orderNumber' => $order->getOrderNumber(),
                    'token' => $order->getTrackingToken()
                ], UrlGeneratorInterface::ABSOLUTE_URL);
                $this->logger->info('Tracking URL générée', ['url' => $trackingUrl]);
            }

            // Construire le contenu HTML de l'email
            $htmlContent = $this->renderView('emails/order_confirmation.html.twig', [
                'order' => $order,
                'trackingUrl' => $trackingUrl,
            ]);

            $this->logger->info('Template HTML généré', [
                'html_length' => strlen($htmlContent)
            ]);

            // Envoyer l'email via Brevo
            $this->emailService->sendEmail(
                to: $recipientEmail,
                toName: $recipientName,
                subject: 'Confirmation de commande #' . $order->getOrderNumber(),
                htmlContent: $htmlContent
            );

            $this->logger->info('=== EMAIL CONFIRMATION ENVOYÉ AVEC SUCCÈS ===', [
                'order_number' => $order->getOrderNumber(),
                'recipient' => $recipientEmail,
            ]);

        } catch (\Exception $e) {
            $this->logger->error('ERREUR ENVOI EMAIL CONFIRMATION', [
                'order_number' => $order->getOrderNumber(),
                'error_message' => $e->getMessage(),
                'error_class' => get_class($e),
                'trace' => $e->getTraceAsString(),
            ]);
            // Ne pas relancer l'exception pour ne pas bloquer le flux de paiement
            // mais l'erreur est maintenant loggée dans Symfony
        }
    }
}
