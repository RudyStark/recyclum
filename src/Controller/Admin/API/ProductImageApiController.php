<?php

namespace App\Controller\Admin\API;

use App\Entity\Product;
use App\Entity\ProductImage;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/admin/api/product-images')]
class ProductImageApiController extends AbstractController
{
    private EntityManagerInterface $em;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->em = $entityManager;
    }

    #[Route('/upload', name: 'admin_api_product_image_upload', methods: ['POST'])]
    public function upload(Request $request): Response
    {
        try {
            $productId = $request->request->get('productId');
            $product = $this->em->getRepository(Product::class)->find($productId);

            if (!$product) {
                return $this->json(['error' => 'Produit non trouvé'], 404);
            }

            $file = $request->files->get('file');

            if (!$file) {
                return $this->json(['error' => 'Aucun fichier'], 400);
            }

            $allowedMimes = ['image/jpeg', 'image/png', 'image/webp', 'image/jpg'];
            if (!in_array($file->getMimeType(), $allowedMimes)) {
                return $this->json(['error' => 'Type de fichier non autorisé'], 400);
            }

            if ($file->getSize() > 4 * 1024 * 1024) {
                return $this->json(['error' => 'Fichier trop volumineux (max 4 Mo)'], 400);
            }

            $originalFilename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
            $safeFilename = transliterator_transliterate(
                'Any-Latin; Latin-ASCII; [^A-Za-z0-9_] remove; Lower()',
                $originalFilename
            );
            $newFilename = $safeFilename . '-' . uniqid() . '.' . $file->guessExtension();

            $uploadsDir = $this->getParameter('kernel.project_dir') . '/public/uploads/products';

            if (!is_dir($uploadsDir)) {
                mkdir($uploadsDir, 0777, true);
            }

            $file->move($uploadsDir, $newFilename);

            $lastImage = $this->em->getRepository(ProductImage::class)
                ->createQueryBuilder('pi')
                ->where('pi.product = :product')
                ->setParameter('product', $product)
                ->orderBy('pi.position', 'DESC')
                ->setMaxResults(1)
                ->getQuery()
                ->getOneOrNullResult();

            $position = $lastImage ? $lastImage->getPosition() + 1 : 1;

            $productImage = new ProductImage();
            $productImage->setProduct($product);
            $productImage->setFilename($newFilename);
            $productImage->setPosition($position);
            $productImage->setIsMain(false);

            $this->em->persist($productImage);
            $this->em->flush();

            return $this->json([
                'success' => true,
                'id' => $productImage->getId(),
                'filename' => $newFilename,
                'position' => $position,
                'url' => '/uploads/products/' . $newFilename
            ]);
        } catch (\Exception $e) {
            return $this->json(['error' => $e->getMessage()], 500);
        }
    }

    #[Route('/{id}/delete', name: 'admin_api_product_image_delete', methods: ['POST'])]
    public function delete(int $id): Response
    {
        try {
            $image = $this->em->getRepository(ProductImage::class)->find($id);

            if (!$image) {
                return $this->json(['error' => 'Image non trouvée'], 404);
            }

            $uploadsDir = $this->getParameter('kernel.project_dir') . '/public/uploads/products';
            $filepath = $uploadsDir . '/' . $image->getFilename();

            if (file_exists($filepath)) {
                unlink($filepath);
            }

            $this->em->remove($image);
            $this->em->flush();

            return $this->json(['success' => true]);
        } catch (\Exception $e) {
            return $this->json(['error' => $e->getMessage()], 500);
        }
    }

    #[Route('/reorder', name: 'admin_api_product_image_reorder', methods: ['POST'])]
    public function reorder(Request $request): Response
    {
        try {
            $data = json_decode($request->getContent(), true);
            $positions = $data['positions'] ?? [];

            foreach ($positions as $imageId => $position) {
                $image = $this->em->getRepository(ProductImage::class)->find($imageId);
                if ($image) {
                    $image->setPosition((int)$position);
                }
            }

            $this->em->flush();

            return $this->json(['success' => true]);
        } catch (\Exception $e) {
            return $this->json(['error' => $e->getMessage()], 500);
        }
    }

    #[Route('/{id}/set-main', name: 'admin_api_product_image_set_main', methods: ['POST'])]
    public function setMain(int $id): Response
    {
        try {
            $image = $this->em->getRepository(ProductImage::class)->find($id);

            if (!$image) {
                return $this->json(['error' => 'Image non trouvée'], 404);
            }

            $product = $image->getProduct();

            if (!$product) {
                return $this->json(['error' => 'Produit non trouvé'], 404);
            }

            // Retire isMain de toutes les images du produit
            foreach ($product->getImages() as $img) {
                $img->setIsMain(false);
            }

            // Définit cette image comme principale
            $image->setIsMain(true);

            $this->em->flush();

            return $this->json(['success' => true]);
        } catch (\Exception $e) {
            return $this->json(['error' => $e->getMessage()], 500);
        }
    }
}
