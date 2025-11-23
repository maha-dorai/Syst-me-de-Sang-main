<?php

namespace App\Controller;

use App\Entity\Don;
use App\Entity\RendezVous;
use App\Form\DonValidationType;
use App\Repository\RendezVousRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/don')]
#[IsGranted('ROLE_ADMIN')]
class DonController extends AbstractController
{
    #[Route('/valider', name: 'app_don_valider', methods: ['GET'])]
    public function valider(RendezVousRepository $rendezVousRepository): Response
    {
        $rendezVousEnAttente = $rendezVousRepository->findRendezVousEffectuesSansDon();
        
        return $this->render('don/valider.html.twig', [
            'rendezVous' => $rendezVousEnAttente,
        ]);
    }

    #[Route('/valider/{id}', name: 'app_don_valider_form', methods: ['GET', 'POST'])]
    public function validerForm(
        Request $request, 
        RendezVous $rendezVous, 
        EntityManagerInterface $entityManager
    ): Response {
        // Vérifier que le rendez-vous est bien effectué
        if ($rendezVous->getStatut() !== 'effectué') {
            $this->addFlash('error', 'Ce rendez-vous n\'est pas marqué comme effectué');
            return $this->redirectToRoute('app_don_valider');
        }

        $don = new Don();
        $don->setRendezVous($rendezVous);
        $don->setDonateurId($rendezVous->getDonateur());
        $don->setDatedon(new \DateTime());
        
        $form = $this->createForm(DonValidationType::class, $don);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Enregistrer le don
            $entityManager->persist($don);
            
            // Mettre à jour la dernière date de don du donateur si apte
            if ($don->isApte()) {
                $donateur = $rendezVous->getDonateur();
                $donateur->setDerniereDateDon($don->getDatedon());
            }
            
            $entityManager->flush();

            $this->addFlash('success', 'Don validé avec succès pour ' . $rendezVous->getDonateur()->getPrenom());

            return $this->redirectToRoute('app_don_valider');
        }

        return $this->render('don/valider_form.html.twig', [
            'rendezVous' => $rendezVous,
            'form' => $form,
        ]);
    }
}