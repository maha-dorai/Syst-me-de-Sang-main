<?php

namespace App\Controller;

use App\Entity\Lieu;
use App\Form\LieuType;
use App\Repository\LieuRepository;
use App\Repository\DonRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/lieu')]
#[IsGranted('ROLE_ADMIN')]
class LieuController extends AbstractController
{
    #[Route('/', name: 'app_lieu_index', methods: ['GET'])]
    public function index(LieuRepository $lieuRepository): Response
    {
        return $this->render('lieu/index.html.twig', [
            'lieus' => $lieuRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'app_lieu_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $lieu = new Lieu();
        $form = $this->createForm(LieuType::class, $lieu);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($lieu);
            $entityManager->flush();

            $this->addFlash('success', 'Le lieu a été créé avec succès !');

            return $this->redirectToRoute('app_lieu_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('lieu/new.html.twig', [
            'lieu' => $lieu,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_lieu_show', methods: ['GET'])]
    public function show(Lieu $lieu): Response
    {
        return $this->render('lieu/show.html.twig', [
            'lieu' => $lieu,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_lieu_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Lieu $lieu, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(LieuType::class, $lieu);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            $this->addFlash('success', 'Le lieu a été modifié avec succès !');

            return $this->redirectToRoute('app_lieu_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('lieu/edit.html.twig', [
            'lieu' => $lieu,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_lieu_delete', methods: ['POST'])]
    public function delete(Request $request, Lieu $lieu, EntityManagerInterface $entityManager, DonRepository $donRepository): Response
    {
        if ($this->isCsrfTokenValid('delete'.$lieu->getId(), $request->request->get('_token'))) {
            
            $nbCollectes = $lieu->getCollecte()->count();
            $nbRendezVous = 0;
            $nbDons = 0;
            
            // ÉTAPE 1 : Parcourir toutes les collectes de ce lieu
            foreach ($lieu->getCollecte() as $collecte) {
                
                // ÉTAPE 1.1 : Pour chaque collecte, supprimer les dons et rendez-vous
                foreach ($collecte->getRendezVous() as $rendezVous) {
                    // Supprimer les dons liés à ce rendez-vous
                    $dons = $donRepository->findBy(['rendezVous' => $rendezVous]);
                    foreach ($dons as $don) {
                        $entityManager->remove($don);
                        $nbDons++;
                    }
                    
                    // Supprimer le rendez-vous
                    $entityManager->remove($rendezVous);
                    $nbRendezVous++;
                }
                
                // ÉTAPE 1.2 : Supprimer la collecte
                $entityManager->remove($collecte);
            }
            
            // ÉTAPE 2 : Supprimer le lieu
            $entityManager->remove($lieu);
            $entityManager->flush();
            
            // Message de confirmation
            $message = "Lieu supprimé avec succès";
            if ($nbCollectes > 0) {
                $message .= " (avec {$nbCollectes} collecte(s)";
                if ($nbRendezVous > 0) {
                    $message .= ", {$nbRendezVous} rendez-vous";
                }
                if ($nbDons > 0) {
                    $message .= " et {$nbDons} don(s)";
                }
                $message .= ")";
            }
            
            $this->addFlash('success', $message);
        }

        return $this->redirectToRoute('app_lieu_index', [], Response::HTTP_SEE_OTHER);
    }
}