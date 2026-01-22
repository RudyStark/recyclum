<?php

namespace App\Controller;

use App\Entity\Product;
use App\Service\CartService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/panier', name: 'cart_')]
class CartController extends AbstractController
{
    public function __construct(
        private CartService $cartService,
        private EntityManagerInterface $em,
    ) {
    }

    /**
     * Affiche le panier
     */
    #[Route('', name: 'index', methods: ['GET'])]
    public function index(): Response
    {
        $cart = $this->cartService->getCurrentCart();

        // Valider le panier
        $validation = $this->cartService->validateCart();

        return $this->render('cart/index.html.twig', [
            'cart' => $cart,
            'validation' => $validation,
        ]);
    }

    /**
     * Ajoute un produit au panier
     */
    #[Route('/add/{id}', name: 'add', methods: ['POST'])]
    public function add(Product $product, Request $request): JsonResponse
    {
        // Vérifier le token CSRF
        $token = $request->request->get('_token');
        if (!$this->isCsrfTokenValid('add_to_cart_' . $product->getId(), $token)) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Token de sécurité invalide.',
            ], Response::HTTP_FORBIDDEN);
        }

        $quantity = max(1, (int) $request->request->get('quantity', 1));

        $result = $this->cartService->addProduct($product, $quantity);

        return new JsonResponse([
            'success' => $result['success'],
            'message' => $result['message'],
            'cartQuantity' => $result['success'] ? $this->cartService->getTotalQuantity() : 0,
            'cartTotal' => $result['success'] ? $this->cartService->getTotal() : 0,
        ]);
    }

    /**
     * Met à jour la quantité d'un item
     */
    #[Route('/update/{itemId}', name: 'update', methods: ['POST'])]
    public function update(int $itemId, Request $request): JsonResponse
    {
        // Vérifier le token CSRF
        $token = $request->request->get('_token');
        if (!$this->isCsrfTokenValid('update_cart_item_' . $itemId, $token)) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Token de sécurité invalide.',
            ], Response::HTTP_FORBIDDEN);
        }

        $quantity = (int) $request->request->get('quantity', 1);

        $result = $this->cartService->updateQuantity($itemId, $quantity);

        if ($result['success']) {
            $cart = $this->cartService->getCurrentCart();
            return new JsonResponse([
                'success' => true,
                'message' => $result['message'],
                'cartQuantity' => $cart->getTotalQuantity(),
                'cartTotal' => $cart->getTotal(),
                'cartTotalFormatted' => $cart->getTotalFormatted(),
            ]);
        }

        return new JsonResponse([
            'success' => false,
            'message' => $result['message'],
        ], Response::HTTP_BAD_REQUEST);
    }

    /**
     * Retire un item du panier
     */
    #[Route('/remove/{itemId}', name: 'remove', methods: ['POST'])]
    public function remove(int $itemId, Request $request): JsonResponse
    {
        // Vérifier le token CSRF
        $token = $request->request->get('_token');
        if (!$this->isCsrfTokenValid('remove_cart_item_' . $itemId, $token)) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Token de sécurité invalide.',
            ], Response::HTTP_FORBIDDEN);
        }

        $result = $this->cartService->removeItem($itemId);

        if ($result['success']) {
            $cart = $this->cartService->getCurrentCart();
            return new JsonResponse([
                'success' => true,
                'message' => $result['message'],
                'cartQuantity' => $cart->getTotalQuantity(),
                'cartTotal' => $cart->getTotal(),
                'cartTotalFormatted' => $cart->getTotalFormatted(),
                'isEmpty' => $cart->isEmpty(),
            ]);
        }

        return new JsonResponse([
            'success' => false,
            'message' => $result['message'],
        ], Response::HTTP_BAD_REQUEST);
    }

    /**
     * Vide le panier
     */
    #[Route('/clear', name: 'clear', methods: ['POST'])]
    public function clear(Request $request): JsonResponse
    {
        // Vérifier le token CSRF
        $token = $request->request->get('_token');
        if (!$this->isCsrfTokenValid('clear_cart', $token)) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Token de sécurité invalide.',
            ], Response::HTTP_FORBIDDEN);
        }

        $result = $this->cartService->clear();

        return new JsonResponse([
            'success' => $result['success'],
            'message' => $result['message'],
        ]);
    }

    /**
     * Récupère les informations du panier (pour widget navbar)
     */
    #[Route('/widget', name: 'widget', methods: ['GET'])]
    public function widget(): JsonResponse
    {
        $cart = $this->cartService->getCurrentCart();

        return new JsonResponse([
            'quantity' => $cart->getTotalQuantity(),
            'total' => $cart->getTotal(),
            'totalFormatted' => $cart->getTotalFormatted(),
            'isEmpty' => $cart->isEmpty(),
        ]);
    }
}
