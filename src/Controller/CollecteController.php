<?php

namespace App\Controller;

use App\Entity\Collecte;
use App\Form\CollecteType;
use App\Repository\CollecteRepository;
use App\Repository\DonRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/admin/collecte')]
class CollecteController extends AbstractController
{
    #[Route('/', name: 'app_collecte_index', methods: ['GET'])]
    public function index(CollecteRepository $collecteRepository): Response
    {
        return $this->render('collecte/index.html.twig', [
            'collectes' => $collecteRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'app_collecte_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $collecte = new Collecte();
        $form = $this->createForm(CollecteType::class, $collecte);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($collecte);
            $entityManager->flush();

            $this->addFlash('success', 'Collecte créée avec succès');

            return $this->redirectToRoute('app_collecte_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('collecte/new.html.twig', [
            'collecte' => $collecte,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_collecte_show', methods: ['GET'])]
    public function show(Collecte $collecte): Response
    {
        return $this->render('collecte/show.html.twig', [
            'collecte' => $collecte,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_collecte_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Collecte $collecte, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(CollecteType::class, $collecte);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            $this->addFlash('success', 'Collecte modifiée avec succès');

            return $this->redirectToRoute('app_collecte_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('collecte/edit.html.twig', [
            'collecte' => $collecte,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_collecte_delete', methods: ['POST'])]
    public function delete(Request $request, Collecte $collecte, EntityManagerInterface $entityManager, DonRepository $donRepository): Response
    {
        if ($this->isCsrfTokenValid('delete'.$collecte->getId(), $request->request->get('_token'))) {
            
            $nbRendezVous = $collecte->getRendezVous()->count();
            $nbDons = 0;
            
            // ÉTAPE 1 : Supprimer tous les DONS liés aux rendez-vous de cette collecte
            foreach ($collecte->getRendezVous() as $rendezVous) {
                // Récupérer les dons liés à ce rendez-vous via le repository
                $dons = $donRepository->findBy(['rendezVous' => $rendezVous]);
                foreach ($dons as $don) {
                    $entityManager->remove($don);
                    $nbDons++;
                }
            }
            
            // ÉTAPE 2 : Supprimer tous les RENDEZ-VOUS de cette collecte
            foreach ($collecte->getRendezVous() as $rendezVous) {
                $entityManager->remove($rendezVous);
            }
            
            // ÉTAPE 3 : Supprimer la COLLECTE
            $entityManager->remove($collecte);
            $entityManager->flush();
            
            // Message de confirmation
            $message = "Collecte supprimée avec succès";
            if ($nbRendezVous > 0) {
                $message .= " (avec {$nbRendezVous} rendez-vous";
                if ($nbDons > 0) {
                    $message .= " et {$nbDons} don(s)";
                }
                $message .= ")";
            }
            
            $this->addFlash('success', $message);
        }

        return $this->redirectToRoute('app_collecte_index', [], Response::HTTP_SEE_OTHER);
    }
}