<?php
// src/Repository/ReclamationArchiveRepository.php

namespace App\Repository;

use App\Entity\ReclamationArchive;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class ReclamationArchiveRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ReclamationArchive::class);
    }
}