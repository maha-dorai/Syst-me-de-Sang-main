<?php

namespace App\Controller;

use App\Repository\DonateurRepository;
use App\Repository\RendezVousRepository;
use App\Repository\StockRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class AdminController extends AbstractController
{
    #[Route('/admin', name: 'admin_dashboard')]
    public function index(
        DonateurRepository $donateurRepo,
        RendezVousRepository $rdvRepo,
        StockRepository $stockRepo
    ): Response {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        // Compter uniquement les donateurs (ROLE_USER) sans l'admin
        $totalDonateurs = $donateurRepo->createQueryBuilder('d')
            ->select('COUNT(d.id)')
            ->where('d.roles LIKE :role')
            ->setParameter('role', '%ROLE_USER%')
            ->getQuery()
            ->getSingleScalarResult();

        $stats = [
            'totalDonateurs' => $totalDonateurs, 
            'stockCritique' => $stockRepo->count(['niveauAlerte' => 'Critique']),
            'rdvAVALIDER' => $rdvRepo->count(['statut' => 'Confirmé']),
        ];

        $stocksCritiques = $stockRepo->findBy(['niveauAlerte' => 'Critique']);

        return $this->render('admin/dashboard.html.twig', [
            'stats' => $stats,
            'stocksCritiques' => $stocksCritiques,
        ]);
    }
}