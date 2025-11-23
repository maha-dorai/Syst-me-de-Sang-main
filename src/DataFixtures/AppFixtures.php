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
        $typeDonListe = ['Sang total', 'Plasma', 'Plaquettes'];
        $collecteStatut = ['Planifiée', 'Terminée', 'En cours'];
        $Statut_Rendez_Vous = ['Confirmé', 'Annulé', 'Effectué'];

        // -----------------------------------------------------------
        // 1️⃣ Création de l'ADMIN
        // -----------------------------------------------------------
        $admin = new Donateur();
        $admin->setEmail('admin@bloodbank.tn');
        $admin->setNom('Administrateur'); // AJOUT: nom
        $admin->setPrenom('System');
        $admin->setGroupeSanguin('O+');
        $admin->setRoles(['ROLE_ADMIN']);

        $hashedAdmin = $this->passwordHasher->hashPassword($admin, 'admin123');
        $admin->setPassword($hashedAdmin);

        $manager->persist($admin);

        // -----------------------------------------------------------
        // 2️⃣ Création de lieux
        // -----------------------------------------------------------
        $lieux = [];
        for ($i = 0; $i < 5; $i++) {
            $lieu = new Lieu();
            $lieu->setNomLieu($faker->company() . ' ' . $faker->citySuffix());
            $lieu->setAdresse($faker->streetAddress());
            $lieu->setVille($faker->city());
            $lieu->setCodePostal($faker->postcode());
            $manager->persist($lieu);
            $lieux[] = $lieu;
        }

        // -----------------------------------------------------------
        // 3️⃣ Création de collectes
        // -----------------------------------------------------------
        $collectes = [];
        for ($i = 0; $i < 10; $i++) {
            $collecte = new Collecte();
            $dateDebut = $faker->dateTimeBetween('-1 month', '+1 month');
            $dateFin = $faker->dateTimeBetween($dateDebut, '+2 months');

            $collecte->setNom('Collecte ' . $faker->city() . ' ' . ($i+1));
            $collecte->setDateDebut($dateDebut);
            $collecte->setDateFin($dateFin);
            $collecte->setCapaciteMaximale($faker->numberBetween(20, 100));
            $collecte->setStatut($faker->randomElement($collecteStatut));
            $collecte->setLieu($faker->randomElement($lieux));

            $manager->persist($collecte);
            $collectes[] = $collecte;
        }

        // -----------------------------------------------------------
        // 4️⃣ Création de 20 donateurs
        // -----------------------------------------------------------
        $donateurs = [];
        for ($i = 0; $i < 20; $i++) {
            $donateur = new Donateur();
            $donateur->setEmail($faker->unique()->email());
            $donateur->setNom($faker->lastName()); // AJOUT: nom
            $donateur->setPrenom($faker->firstName());
            $donateur->setGroupeSanguin($faker->randomElement($GroupeSanguinPossible));
            
            // 50% des donateurs ont une dernière date de don
            if ($faker->boolean(50)) {
                $donateur->setDerniereDateDon($faker->dateTimeBetween('-2 years', '-1 month'));
            }

            $hashedPassword = $this->passwordHasher->hashPassword($donateur, 'user123');
            $donateur->setPassword($hashedPassword);
            $donateur->setRoles(['ROLE_USER']);

            $manager->persist($donateur);
            $donateurs[] = $donateur;
        }

        // -----------------------------------------------------------
        // 5️⃣ Création de rendez-vous AVEC dons (pour données normales)
        // -----------------------------------------------------------
        for ($i = 0; $i < 15; $i++) {
            $rendezVous = new RendezVous();
            $rvDebut = $faker->dateTimeBetween('-1 month', '+1 month');
            $rvFin = (clone $rvDebut)->modify('+1 hour');

            $rendezVous->setDateHeureDebut($rvDebut);
            $rendezVous->setDateHeureFin($rvFin);
            $rendezVous->setStatut($faker->randomElement(['Confirmé', 'Effectué']));
            $rendezVous->setDonateur($faker->randomElement($donateurs));
            $rendezVous->setCollecte($faker->randomElement($collectes));

            $manager->persist($rendezVous);

            // Créer un don associé pour ces rendez-vous
            $don = new Don();
            $don->setDonateurId($rendezVous->getDonateur());
            $don->setRendezVous($rendezVous);
            $don->setDatedon($rvDebut);
            $don->setQuantite($faker->numberBetween(400, 500));
            $don->setTypeDon($faker->randomElement($typeDonListe));
            $don->setApte($faker->boolean(80)); // 80% de dons aptes
            $don->setCommentaire($faker->boolean(30) ? $faker->realText(50) : null);

            $manager->persist($don);
        }

        // -----------------------------------------------------------
        // 6️⃣ Création de rendez-vous SANS dons (pour la validation)
        // -----------------------------------------------------------
        for ($i = 0; $i < 8; $i++) {
            $rendezVous = new RendezVous();
            $rvDebut = $faker->dateTimeBetween('-1 week', '+1 week');
            $rvFin = (clone $rvDebut)->modify('+1 hour');

            $rendezVous->setDateHeureDebut($rvDebut);
            $rendezVous->setDateHeureFin($rvFin);
            $rendezVous->setStatut('Effectué'); // ← IMPORTANT: Statut "Effectué"
            $rendezVous->setDonateur($faker->randomElement($donateurs));
            $rendezVous->setCollecte($faker->randomElement($collectes));

            $manager->persist($rendezVous);
            
            // PAS de don créé pour ces rendez-vous ✅
        }

        // -----------------------------------------------------------
        // 7️⃣ Création des stocks
        // -----------------------------------------------------------
        foreach ($GroupeSanguinPossible as $groupe) {
            $stock = new Stock();
            $stock->setGroupeSanguin($groupe);
            $niveau = $faker->randomFloat(1, 0.5, 5);
            $stock->setNiveauActuel($niveau);

            if ($niveau <= 1.5) {
                $stock->setNiveauAlerte("Critique");
            } elseif ($niveau < 3) {
                $stock->setNiveauAlerte("Alerte");
            } else {
                $stock->setNiveauAlerte("Normal");
            }

            $stock->setDernierMiseAJour($faker->dateTimeThisMonth());
            $manager->persist($stock);
        }

        // -----------------------------------------------------------
        // SAVE TO DB
        // -----------------------------------------------------------
        $manager->flush();
    }
}