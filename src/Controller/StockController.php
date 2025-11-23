<?php

namespace App\Controller;

use App\Repository\StockRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/stock')]
#[IsGranted('ROLE_ADMIN')]
class StockController extends AbstractController
{
    #[Route('/', name: 'app_stock_index', methods: ['GET'])]
    public function index(StockRepository $stockRepository): Response
    {
        $stocks = $stockRepository->findAll();
        
        // Calculer les statistiques
        $totalUnites = 0;
        $alertes = 0;
        
        foreach ($stocks as $stock) {
            $totalUnites += $stock->getNiveauActuel();
            if ($stock->getNiveauAlerte() === 'critique' || $stock->getNiveauAlerte() === 'faible') {
                $alertes++;
            }
        }
        
        return $this->render('stock/index.html.twig', [
            'stocks' => $stocks,
            'totalUnites' => $totalUnites,
            'alertes' => $alertes,
        ]);
    }
    
    #[Route('/{id}/update', name: 'app_stock_update', methods: ['POST'])]
    public function update(Request $request, int $id, StockRepository $stockRepository, EntityManagerInterface $entityManager): Response
    {
        $stock = $stockRepository->find($id);
        
        if (!$stock) {
            $this->addFlash('error', 'Stock introuvable');
            return $this->redirectToRoute('app_stock_index');
        }
        
        if ($this->isCsrfTokenValid('update'.$stock->getId(), $request->request->get('_token'))) {
            $niveau = $request->request->get('niveau');
            
            if ($niveau !== null && is_numeric($niveau) && $niveau >= 0) {
                $stock->setNiveauActuel((int)$niveau);
                $stock->setDernierMiseAJour(new \DateTime());
                
                $entityManager->flush();
                
                $this->addFlash('success', "Stock {$stock->getGroupeSanguin()} mis à jour : {$niveau} unités");
            } else {
                $this->addFlash('error', 'Valeur invalide');
            }
        }
        
        return $this->redirectToRoute('app_stock_index');
    }
}