<?php

namespace App\Controller;

use App\Repository\CollecteRepository;
use App\Repository\DonateurRepository;
use App\Repository\StockRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class AccueilController extends AbstractController
{



   /* #[Route('/donateur', name: 'app_home')]
    public function allDonateurs(DonateurRepository $donateurRepository): Response
    {
        $donateurs= $donateurRepository->findAll();
     

        return $this->render('home/index.html.twig', [
            'donateur_list' => $donateurs,
        ]);
    }
*/
    #[Route('/', name: 'Accueil')]
    public function allCollectes(CollecteRepository $collecteRepository,StockRepository $stockRepository): Response
    {
        $collectes= $collecteRepository->findAll();
        $stock= $stockRepository->findAll();
     

        return $this->render('home/accueil.html.twig', [
            'collectes_list' => $collectes,
            'stock' => $stock,
        ]);
    }

    

   
}
