<?php

namespace App\DataFixtures;

use App\Entity\Collecte;
use App\Entity\Don;
use App\Entity\Donateur;
use App\Entity\Lieu;
use App\Entity\RendezVous;
use App\Entity\Stock;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Faker\Factory;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AppFixtures extends Fixture
{
    private UserPasswordHasherInterface $passwordHasher;

    public function __construct(UserPasswordHasherInterface $passwordHasher)
    {
        $this->passwordHasher = $passwordHasher;
    }

    public function load(ObjectManager $manager): void
    {
        $faker = Factory::create('fr_FR');

        $GroupeSanguinPossible = ['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'];
        $typeDonListe = ['Sangtotal', 'plasma', 'plaquettes'];
        $collecteStatut = ['Planifiée', 'Terminée'];
        $Statut_Rendez_Vous = ['Confirmé', 'Annulé', 'Effectué'];

        // -----------------------------------------------------------
        // 1️⃣ Création de l'ADMIN
        // -----------------------------------------------------------
        $admin = new Donateur();
        $admin->setEmail('admin@bloodbank.tn');
        $admin->setPrenom('Admin');
        $admin->setGroupeSanguin('O+');
        $admin->setRoles(['ROLE_ADMIN']);

        $hashedAdmin = $this->passwordHasher->hashPassword($admin, 'admin123');
        $admin->setPassword($hashedAdmin);

        $manager->persist($admin);


        // -----------------------------------------------------------
        // 2️⃣ Création de 20 donateurs + leurs données
        // -----------------------------------------------------------
        for ($i = 0; $i < 20; $i++) {

            // --- Donateur ---
            $donateur = new Donateur();
            $donateur->setEmail($faker->unique()->email());
            $donateur->setPrenom($faker->firstName());
            $donateur->setGroupeSanguin($faker->randomElement($GroupeSanguinPossible));
            $donateur->setDerniereDateDon($faker->dateTimeBetween('-2 years', 'now'));

            $hashedPassword = $this->passwordHasher->hashPassword($donateur, 'user123');
            $donateur->setPassword($hashedPassword);

            // 👉 Tous les donateurs ont ROLE_USER
            $donateur->setRoles(['ROLE_USER']);

            // --- Lieu ---
            $lieu = new Lieu();
            $lieu->setNomLieu($faker->city());
            $lieu->setAdresse($faker->address());
            $lieu->setVille($faker->city());
            $lieu->setCodePostal($faker->postcode());

            // --- Collecte ---
            $collecte = new Collecte();
            $dateDebut = $faker->dateTimeBetween('now', '+1 month');
            $dateFin = $faker->dateTimeBetween($dateDebut, '+1 month');

            $collecte->setNom('Collecte '.($i+1));
            $collecte->setDateDebut($dateDebut);
            $collecte->setDateFin($dateFin);
            $collecte->setCapaciteMaximale($faker->numberBetween(20, 100));
            $collecte->setStatut($faker->randomElement($collecteStatut));
            $collecte->setLieu($lieu);

            // --- Rendez-vous ---
            $rendezVous = new RendezVous();
            $rvDebut = $faker->dateTimeBetween('now', '+2 month');
            $rvFin = $faker->dateTimeBetween($rvDebut, '+1 hour');

            $rendezVous->setDateHeureDebut($rvDebut);
            $rendezVous->setDateHeureFin($rvFin);
            $rendezVous->setStatut($faker->randomElement($Statut_Rendez_Vous));
            $rendezVous->setDonateur($donateur);
            $rendezVous->setCollecte($collecte);

            // --- Don ---
            $don = new Don();
            $don->setDonateurId($donateur);
            $don->setRendezVous($rendezVous);
            $don->setDatedon($faker->dateTimeThisYear());
            $don->setQuantite($faker->numberBetween(100, 500));
            $don->setTypeDon($faker->randomElement($typeDonListe));
            $don->setApte($faker->boolean());
            $don->setCommentaire($faker->realText(80));

            // --- Stock ---
            $stock = new Stock();
            $stock->setGroupeSanguin($faker->randomElement($GroupeSanguinPossible));
            $niveau = $faker->randomFloat(1, 0.5, 5);
            $stock->setNiveauActuel($niveau);

            if ($niveau <= 1.5) {
                $stock->setNiveauAlerte("Critique");
            } elseif ($niveau < 3) {
                $stock->setNiveauAlerte("Alerte");
            } else {
                $stock->setNiveauAlerte("Normal");
            }

            $stock->setDernierMiseAJour($faker->dateTimeThisYear());

            // --- Persist ---
            $manager->persist($donateur);
            $manager->persist($lieu);
            $manager->persist($collecte);
            $manager->persist($rendezVous);
            $manager->persist($don);
            $manager->persist($stock);
        }

        // -----------------------------------------------------------
        // SAVE TO DB
        // -----------------------------------------------------------
        $manager->flush();
    }
}
