<?php

namespace App\Controller;

use App\Entity\Don;
use App\Entity\RendezVous;
use App\Form\DonType;
use App\Repository\DonateurRepository;
use App\Repository\RendezVousRepository;
use App\Repository\StockRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
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

    #[Route('/admin/don/valider', name: 'admin_don_valider')]
    public function valider(
        Request $request,
        RendezVousRepository $rdvRepo,
        EntityManagerInterface $entityManager
    ): Response {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        // Récupérer les rendez-vous "Effectué" sans Don associé
        $rendezVous = $rdvRepo->findEffectuesSansDon();

        // Créer un tableau pour stocker les formulaires
        $forms = [];

        // Traiter le formulaire soumis si présent
        if ($request->isMethod('POST')) {
            $rdvId = $request->request->get('rendez_vous_id');
            
            if ($rdvId) {
                // Trouver le rendez-vous correspondant
                $rdv = null;
                foreach ($rendezVous as $r) {
                    if ($r->getId() == $rdvId) {
                        $rdv = $r;
                        break;
                    }
                }
                
                if ($rdv) {
                    $don = new Don();
                    $don->setRendezVous($rdv);
                    $don->setDonateurId($rdv->getDonateur());
                    $don->setDatedon(new \DateTime());
                    
                    $form = $this->createForm(DonType::class, $don);
                    $form->handleRequest($request);

                    if ($form->isSubmitted() && $form->isValid()) {
                        // Persister le Don
                        $entityManager->persist($don);
                        
                        // Mettre à jour derniereDateDon du Donateur si le don est apte
                        $donateur = $don->getDonateurId();
                        if ($donateur && $don->isApte()) {
                            $donateur->setDerniereDateDon($don->getDatedon());
                            $entityManager->persist($donateur);
                        }
                        
                        $entityManager->flush();

                        $this->addFlash('success', 'Don validé avec succès pour ' . $donateur->getPrenom());

                        return $this->redirectToRoute('admin_don_valider');
                    }
                    
                    $forms[$rdvId] = $form->createView();
                }
            }
        }

        // Créer les formulaires pour les autres rendez-vous
        foreach ($rendezVous as $rdv) {
            if (!isset($forms[$rdv->getId()])) {
                $don = new Don();
                $don->setRendezVous($rdv);
                $don->setDonateurId($rdv->getDonateur());
                $don->setDatedon(new \DateTime());
                
                $form = $this->createForm(DonType::class, $don);
                $forms[$rdv->getId()] = $form->createView();
            }
        }

        return $this->render('admin/don/valider.html.twig', [
            'rendezVous' => $rendezVous,
            'forms' => $forms,
        ]);
    }
}