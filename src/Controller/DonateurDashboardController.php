<?php

namespace App\Controller;

use App\Repository\RendezVousRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Response;

class DonateurDashboardController extends AbstractController
{
    #[Route('/donateur', name: 'donateur_dashboard')]
    public function index(RendezVousRepository $rendezVousRepository): Response
    {
        $user = $this->getUser();

        // Récupère le prochain rendez-vous du donateur connecté
  

        return $this->render('donateur/dashboard_don.html.twig', [
           
        ]);
    }
}
