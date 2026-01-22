<?php

namespace App\Service;

use App\Entity\Cart;
use App\Entity\CartItem;
use App\Entity\Product;
use App\Entity\User;
use App\Repository\CartRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

/**
 * Service de gestion du panier
 * Gère les paniers en session (invités) et en base (utilisateurs)
 */
class CartService
{
    private const SESSION_KEY = 'cart_id';
    private const SESSION_ID_KEY = 'cart_session_id';

    public function __construct(
        private EntityManagerInterface $em,
        private CartRepository $cartRepository,
        private RequestStack $requestStack,
        private TokenStorageInterface $tokenStorage,
    ) {
    }

    /**
     * Récupère le panier actif (invité ou utilisateur)
     */
    public function getCurrentCart(): Cart
    {
        $user = $this->getAuthenticatedUser();

        if ($user) {
            return $this->getUserCart($user);
        }

        return $this->getGuestCart();
    }

    /**
     * Récupère ou crée le panier d'un utilisateur authentifié
     */
    private function getUserCart(User $user): Cart
    {
        $cart = $this->cartRepository->findActiveByUser($user);

        if (!$cart) {
            $cart = new Cart();
            $cart->setUser($user);
            $this->em->persist($cart);
            $this->em->flush();
        }

        return $cart;
    }

    /**
     * Récupère ou crée le panier d'un invité (basé sur session)
     */
    private function getGuestCart(): Cart
    {
        $session = $this->requestStack->getSession();
        $sessionId = $session->get(self::SESSION_ID_KEY);

        // Générer un session ID unique si nécessaire
        if (!$sessionId) {
            $sessionId = bin2hex(random_bytes(16));
            $session->set(self::SESSION_ID_KEY, $sessionId);
        }

        $cart = $this->cartRepository->findActiveBySessionId($sessionId);

        if (!$cart) {
            $cart = new Cart();
            $cart->setSessionId($sessionId);
            $this->em->persist($cart);
            $this->em->flush();

            $session->set(self::SESSION_KEY, $cart->getId());
        }

        return $cart;
    }

    /**
     * Ajoute un produit au panier
     *
     * @param Product $product
     * @param int $quantity
     * @return array ['success' => bool, 'message' => string, 'cart' => Cart]
     */
    public function addProduct(Product $product, int $quantity = 1): array
    {
        // Vérifications
        if (!$product->isPublished()) {
            return [
                'success' => false,
                'message' => 'Ce produit n\'est plus disponible.',
                'cart' => null,
            ];
        }

        if ($product->getStock() < 1) {
            return [
                'success' => false,
                'message' => 'Ce produit est en rupture de stock.',
                'cart' => null,
            ];
        }

        if ($quantity < 1 || $quantity > 10) {
            return [
                'success' => false,
                'message' => 'Quantité invalide (entre 1 et 10).',
                'cart' => null,
            ];
        }

        $cart = $this->getCurrentCart();

        // Vérifier si le produit est déjà dans le panier
        $existingItem = $cart->findItemByProduct($product);

        if ($existingItem) {
            $newQuantity = $existingItem->getQuantity() + $quantity;

            // Limiter à 10 unités par produit
            if ($newQuantity > 10) {
                return [
                    'success' => false,
                    'message' => 'Vous ne pouvez pas ajouter plus de 10 unités de ce produit.',
                    'cart' => $cart,
                ];
            }

            // Vérifier le stock disponible
            if ($newQuantity > $product->getStock()) {
                return [
                    'success' => false,
                    'message' => sprintf('Stock insuffisant. Seulement %d unité(s) disponible(s).', $product->getStock()),
                    'cart' => $cart,
                ];
            }

            $existingItem->setQuantity($newQuantity);
            $message = sprintf('Quantité mise à jour : %d × %s', $newQuantity, $product->getTitle());
        } else {
            // Vérifier le stock pour le nouvel ajout
            if ($quantity > $product->getStock()) {
                return [
                    'success' => false,
                    'message' => sprintf('Stock insuffisant. Seulement %d unité(s) disponible(s).', $product->getStock()),
                    'cart' => $cart,
                ];
            }

            $cartItem = new CartItem();
            $cartItem->setCart($cart);
            $cartItem->setProduct($product);
            $cartItem->setQuantity($quantity);
            $cartItem->setUnitPrice($product->getPrice());

            $this->em->persist($cartItem);
            $cart->addItem($cartItem);

            $message = sprintf('%s ajouté au panier', $product->getTitle());
        }

        $cart->setUpdatedAt(new \DateTimeImmutable());
        $this->em->flush();

        return [
            'success' => true,
            'message' => $message,
            'cart' => $cart,
        ];
    }

    /**
     * Met à jour la quantité d'un item
     *
     * @param int $itemId
     * @param int $quantity
     * @return array ['success' => bool, 'message' => string]
     */
    public function updateQuantity(int $itemId, int $quantity): array
    {
        $cart = $this->getCurrentCart();
        $item = null;

        foreach ($cart->getItems() as $cartItem) {
            if ($cartItem->getId() === $itemId) {
                $item = $cartItem;
                break;
            }
        }

        if (!$item) {
            return [
                'success' => false,
                'message' => 'Produit non trouvé dans le panier.',
            ];
        }

        if ($quantity < 1) {
            return $this->removeItem($itemId);
        }

        if ($quantity > 10) {
            return [
                'success' => false,
                'message' => 'Quantité maximale : 10 unités par produit.',
            ];
        }

        $product = $item->getProduct();

        if ($quantity > $product->getStock()) {
            return [
                'success' => false,
                'message' => sprintf('Stock insuffisant. Seulement %d unité(s) disponible(s).', $product->getStock()),
            ];
        }

        $item->setQuantity($quantity);
        $cart->setUpdatedAt(new \DateTimeImmutable());
        $this->em->flush();

        return [
            'success' => true,
            'message' => 'Quantité mise à jour.',
        ];
    }

    /**
     * Retire un item du panier
     *
     * @param int $itemId
     * @return array ['success' => bool, 'message' => string]
     */
    public function removeItem(int $itemId): array
    {
        $cart = $this->getCurrentCart();
        $item = null;

        foreach ($cart->getItems() as $cartItem) {
            if ($cartItem->getId() === $itemId) {
                $item = $cartItem;
                break;
            }
        }

        if (!$item) {
            return [
                'success' => false,
                'message' => 'Produit non trouvé dans le panier.',
            ];
        }

        $productTitle = $item->getProduct()->getTitle();
        $cart->removeItem($item);
        $this->em->remove($item);
        $cart->setUpdatedAt(new \DateTimeImmutable());
        $this->em->flush();

        return [
            'success' => true,
            'message' => sprintf('%s retiré du panier', $productTitle),
        ];
    }

    /**
     * Vide complètement le panier
     *
     * @return array ['success' => bool, 'message' => string]
     */
    public function clear(): array
    {
        $cart = $this->getCurrentCart();

        foreach ($cart->getItems() as $item) {
            $this->em->remove($item);
        }

        $cart->getItems()->clear();
        $cart->setUpdatedAt(new \DateTimeImmutable());
        $this->em->flush();

        return [
            'success' => true,
            'message' => 'Panier vidé',
        ];
    }

    /**
     * Fusionne le panier invité avec le panier utilisateur lors de la connexion
     */
    public function mergeGuestCartOnLogin(User $user): void
    {
        $session = $this->requestStack->getSession();
        $guestSessionId = $session->get(self::SESSION_ID_KEY);

        if (!$guestSessionId) {
            return; // Pas de panier invité
        }

        $guestCart = $this->cartRepository->findActiveBySessionId($guestSessionId);

        if (!$guestCart || $guestCart->isEmpty()) {
            return; // Panier invité vide ou inexistant
        }

        // Fusionner avec le panier utilisateur
        $this->cartRepository->mergeGuestCartToUserCart($guestCart, $user);

        // Nettoyer la session
        $session->remove(self::SESSION_KEY);
        $session->remove(self::SESSION_ID_KEY);
    }

    /**
     * Récupère le nombre total d'articles dans le panier
     */
    public function getTotalQuantity(): int
    {
        $cart = $this->getCurrentCart();
        return $cart->getTotalQuantity();
    }

    /**
     * Récupère le montant total du panier
     */
    public function getTotal(): int
    {
        $cart = $this->getCurrentCart();
        return $cart->getTotal();
    }

    /**
     * Vérifie si le panier est vide
     */
    public function isEmpty(): bool
    {
        $cart = $this->getCurrentCart();
        return $cart->isEmpty();
    }

    /**
     * Valide que tous les produits du panier sont disponibles et en stock
     *
     * @return array ['valid' => bool, 'errors' => array]
     */
    public function validateCart(): array
    {
        $cart = $this->getCurrentCart();
        $errors = [];

        foreach ($cart->getItems() as $item) {
            $product = $item->getProduct();

            if (!$product->isPublished()) {
                $errors[] = sprintf('%s n\'est plus disponible.', $product->getTitle());
            }

            if ($product->getStock() < 1) {
                $errors[] = sprintf('%s est en rupture de stock.', $product->getTitle());
            } elseif ($product->getStock() < $item->getQuantity()) {
                $errors[] = sprintf(
                    '%s : seulement %d unité(s) disponible(s), vous en avez %d dans votre panier.',
                    $product->getTitle(),
                    $product->getStock(),
                    $item->getQuantity()
                );
            }
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
        ];
    }

    /**
     * Récupère l'utilisateur authentifié
     */
    private function getAuthenticatedUser(): ?User
    {
        $token = $this->tokenStorage->getToken();

        if (!$token) {
            return null;
        }

        $user = $token->getUser();

        return $user instanceof User ? $user : null;
    }

    /**
     * Nettoie les paniers expirés (à appeler via CRON)
     */
    public function cleanExpiredCarts(): int
    {
        return $this->cartRepository->deleteExpiredCarts();
    }
}
