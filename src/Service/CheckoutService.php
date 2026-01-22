<?php

namespace App\Service;

use App\Entity\Cart;
use App\Entity\Order;
use Symfony\Component\HttpFoundation\RequestStack;

class CheckoutService
{
    private const SESSION_KEY_DELIVERY = 'checkout_delivery';
    private const SESSION_KEY_ORDER_ID = 'checkout_order_id';

    public function __construct(
        private RequestStack $requestStack
    ) {}

    /**
     * Récupère la session courante
     */
    private function getSession()
    {
        return $this->requestStack->getSession();
    }

    /**
     * Sauvegarde les données de livraison en session
     */
    public function saveDeliveryData(array $data): void
    {
        $this->getSession()->set(self::SESSION_KEY_DELIVERY, $data);
    }

    /**
     * Récupère les données de livraison depuis la session
     */
    public function getDeliveryData(): ?array
    {
        return $this->getSession()->get(self::SESSION_KEY_DELIVERY);
    }

    /**
     * Sauvegarde l'ID de la commande en cours
     */
    public function saveOrderId(int $orderId): void
    {
        $this->getSession()->set(self::SESSION_KEY_ORDER_ID, $orderId);
    }

    /**
     * Récupère l'ID de la commande en cours
     */
    public function getOrderId(): ?int
    {
        return $this->getSession()->get(self::SESSION_KEY_ORDER_ID);
    }

    /**
     * Nettoie les données de checkout
     */
    public function clear(): void
    {
        $session = $this->getSession();
        $session->remove(self::SESSION_KEY_DELIVERY);
        $session->remove(self::SESSION_KEY_ORDER_ID);
    }

    /**
     * Vérifie que le panier est valide pour le checkout
     */
    public function validateCart(Cart $cart): array
    {
        $errors = [];

        if ($cart->isEmpty()) {
            $errors[] = 'Votre panier est vide';
        }

        foreach ($cart->getItems() as $item) {
            $product = $item->getProduct();

            if (!$product->isPublished()) {
                $errors[] = sprintf('Le produit "%s" n\'est plus disponible', $product->getTitle());
            }

            if ($product->getStock() < $item->getQuantity()) {
                $errors[] = sprintf(
                    'Stock insuffisant pour "%s" (disponible: %d, demandé: %d)',
                    $product->getTitle(),
                    $product->getStock(),
                    $item->getQuantity()
                );
            }
        }

        return $errors;
    }

    /**
     * Vérifie qu'une adresse de livraison est valide
     */
    public function validateDeliveryAddress(array $data): array
    {
        $errors = [];
        $required = ['firstName', 'lastName', 'street', 'postalCode', 'city', 'phone'];

        // Ajouter email aux champs requis si présent (invité)
        if (isset($data['email'])) {
            $required[] = 'email';
        }

        foreach ($required as $field) {
            if (empty($data[$field])) {
                $errors[$field] = 'Ce champ est obligatoire';
            }
        }

        // Validation email
        if (!empty($data['email']) && !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Email invalide';
        }

        // Validation code postal (France)
        if (!empty($data['postalCode']) && !preg_match('/^[0-9]{5}$/', $data['postalCode'])) {
            $errors['postalCode'] = 'Code postal invalide (5 chiffres attendus)';
        }

        // Validation téléphone
        if (!empty($data['phone']) && !preg_match('/^[0-9]{10}$/', $data['phone'])) {
            $errors['phone'] = 'Numéro de téléphone invalide (10 chiffres attendus)';
        }

        // Validation Île-de-France uniquement
        if (!empty($data['postalCode'])) {
            $dept = substr($data['postalCode'], 0, 2);
            $validDepts = ['75', '77', '78', '91', '92', '93', '94', '95'];

            if (!in_array($dept, $validDepts)) {
                $errors['postalCode'] = 'Nous livrons uniquement en Île-de-France';
            }
        }

        return $errors;
    }

    /**
     * Calcule le récapitulatif du checkout
     */
    public function getSummary(Cart $cart, string $deliveryType): array
    {
        $subtotal = $cart->getTotal();
        $deliveryPrice = match($deliveryType) {
            'simple' => 2000,
            'with_installation' => 3000,
            default => 0
        };
        $total = $subtotal + $deliveryPrice;

        return [
            'subtotal' => $subtotal,
            'deliveryPrice' => $deliveryPrice,
            'total' => $total,
            'subtotalFormatted' => number_format($subtotal / 100, 2, ',', ' ') . ' €',
            'deliveryPriceFormatted' => number_format($deliveryPrice / 100, 2, ',', ' ') . ' €',
            'totalFormatted' => number_format($total / 100, 2, ',', ' ') . ' €',
            'itemsCount' => $cart->getTotalQuantity(),
        ];
    }

    /**
     * Vérifie si le checkout peut continuer
     */
    public function canProceed(Cart $cart): bool
    {
        return empty($this->validateCart($cart));
    }

    /**
     * Récupère l'étape actuelle du checkout
     */
    public function getCurrentStep(): int
    {
        if ($this->getOrderId()) {
            return 3; // Paiement
        }

        if ($this->getDeliveryData()) {
            return 2; // Récapitulatif
        }

        return 1; // Livraison
    }
}
