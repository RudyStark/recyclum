<?php

namespace App\Controller\Admin;

use App\Entity\ApplianceModel;
use App\Entity\Brand;
use App\Entity\BuybackRequest;
use App\Entity\Category;
use App\Entity\Product;
use App\Entity\ProductImage;
use App\Entity\RepairRequest;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminDashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\Dashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\MenuItem;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use EasyCorp\Bundle\EasyAdminBundle\Config\Assets;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;

#[AdminDashboard(routePath: '/admin', routeName: 'admin')]
class DashboardController extends AbstractDashboardController
{
    private EntityManagerInterface $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    public function index(): Response
    {
        // Calcul des compteurs pour le menu
        $pendingRepairCount = $this->entityManager
            ->getRepository(RepairRequest::class)
            ->count(['status' => 'pending']);

        $totalModelsCount = $this->entityManager
            ->getRepository(ApplianceModel::class)
            ->count(['isActive' => true]);

        $pendingBuybackCount = $this->entityManager
            ->getRepository(BuybackRequest::class)
            ->count(['status' => 'pending']);

        // Statistiques pour le dashboard
        $totalProducts = $this->entityManager->getRepository(Product::class)->count([]);
        $totalRepairs = $this->entityManager->getRepository(RepairRequest::class)->count([]);
        $totalBuybacks = $this->entityManager->getRepository(BuybackRequest::class)->count([]);

        return $this->render('admin/dashboard.html.twig', [
            // Compteurs pour le menu
            'pending_repair_count' => $pendingRepairCount,
            'total_models_count' => $totalModelsCount,
            'pending_buyback_count' => $pendingBuybackCount,

            // Stats pour le dashboard
            'total_products' => $totalProducts,
            'total_repairs' => $totalRepairs,
            'total_buybacks' => $totalBuybacks,
        ]);
    }

    public function configureAssets(): Assets
    {
        return Assets::new()
            ->addWebpackEncoreEntry('admin');
    }

    public function configureDashboard(): Dashboard
    {
        return Dashboard::new()
            ->setTitle('Recyclum BackOffice');
    }

    public function configureCrud(): Crud
    {
        return Crud::new()
            ->addFormTheme('admin/form/image_upload_theme.html.twig')
            ->setPaginatorPageSize(20)
            ->showEntityActionsInlined()
            ->setEntityPermission('ROLE_ADMIN');
    }

    public function configureMenuItems(): iterable
    {
        // Retourne un tableau vide pour cacher le menu par défaut d'EasyAdmin
        // On utilise notre menu custom dans menu.html.twig
        return [];
    }
}
