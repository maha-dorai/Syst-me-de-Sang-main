<?php

namespace App\Repository;

use App\Entity\RendezVous;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<RendezVous>
 */
class RendezVousRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, RendezVous::class);
    }

    /**
     * Trouve les rendez-vous effectués qui n'ont pas encore de don enregistré
     * 
     * @return RendezVous[]
     */
    public function findRendezVousEffectuesSansDon(): array
    {
        return $this->createQueryBuilder('r')
            ->leftJoin('App\Entity\Don', 'd', 'WITH', 'd.rendezVous = r.id')
            ->where('r.statut = :statut')
            ->andWhere('d.id IS NULL')
            ->setParameter('statut', 'effectué')
            ->orderBy('r.dateHeureDebut', 'DESC')
            ->getQuery()
            ->getResult();
    }
}