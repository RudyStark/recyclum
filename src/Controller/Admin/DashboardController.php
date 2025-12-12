<?php

namespace App\Controller\Admin;

use App\Entity\BuybackRequest;
use App\Entity\Product;
use App\Entity\RepairRequest;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminDashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\Dashboard;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;
use Symfony\Component\HttpFoundation\Response;
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
        // Repositories
        $productRepo = $this->entityManager->getRepository(Product::class);
        $buybackRepo = $this->entityManager->getRepository(BuybackRequest::class);
        $repairRepo = $this->entityManager->getRepository(RepairRequest::class);

        // ===== KPIs =====
        $stats = [
            'total_products' => $productRepo->countPublished(), // Produits disponibles à la vente
            'active_buybacks' => $buybackRepo->countActiveRequests(), // Rachats en cours
            'active_repairs' => $repairRepo->countActiveRepairs(), // Réparations en cours
            'to_pay_amount' => $buybackRepo->getTotalToPay(), // Montant à payer (rachats collectés)
            'pending_buybacks' => $buybackRepo->countPendingValidation(), // Rachats à valider
            'pending_repairs' => $repairRepo->countPendingRepairs(), // Réparations à traiter
            'urgent_repairs' => $repairRepo->countUrgentPending(), // Réparations urgentes
        ];

        // ===== GRAPHIQUES =====
        // Rachats par statut
        $buybacksByStatus = $buybackRepo->getBuybacksByStatus();
        $buybacksData = [
            'labels' => ['En attente', 'Validé', 'RDV planifié', 'Collecté', 'Payé'],
            'data' => [
                $buybacksByStatus['pending'],
                $buybacksByStatus['validated'],
                $buybacksByStatus['appointment_scheduled'] + $buybacksByStatus['awaiting_collection'],
                $buybacksByStatus['collected'],
                $buybacksByStatus['paid'],
            ],
        ];

        // Réparations par statut
        $repairsByStatus = $repairRepo->getRepairsByStatus();
        $repairsData = [
            'labels' => ['En attente', 'Contacté', 'Planifié', 'Terminé'],
            'data' => [
                $repairsByStatus['pending'],
                $repairsByStatus['contacted'],
                $repairsByStatus['scheduled'],
                $repairsByStatus['completed'],
            ],
        ];

        // ===== ACTIVITÉ RÉCENTE =====
        $recentProducts = $productRepo->getRecentProducts(5);
        $recentBuybacks = $buybackRepo->getRecentRequests(5);
        $recentRepairs = $repairRepo->getRecentRepairs(5);

        return $this->render('admin/dashboard/index.html.twig', [
            'stats' => $stats,
            'buybacks_data' => $buybacksData,
            'repairs_data' => $repairsData,
            'recent_products' => $recentProducts,
            'recent_buybacks' => $recentBuybacks,
            'recent_repairs' => $recentRepairs,
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
