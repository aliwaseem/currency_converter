<?php

// src/Repository/CurrencyRepository.php

namespace App\Repository;

use App\Entity\Currency;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * Repository for Currency entity.
 * 
 * This repository provides methods to find and manage currencies in the system.
 * It extends ServiceEntityRepository to get basic CRUD operations.
 */
class CurrencyRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Currency::class);
    }

    /**
     * Find a currency by its ISO 4217 code.
     *
     * @param string $code The three-letter currency code (e.g., USD, EUR)
     * @return Currency|null The currency if found, null otherwise
     */
    public function findByCode(string $code): ?Currency
    {
        return $this->findOneBy(['code' => $code]);
    }

    /**
     * Find all currencies with their current exchange rates.
     *
     * @return array<Currency> Array of currencies with their current rates
     */
    public function findAllWithCurrentRates(): array
    {
        return $this->createQueryBuilder('c')
            ->leftJoin('c.exchangeRates', 'r')
            ->where('r.validFrom <= :now')
            ->andWhere('r.validTo >= :now')
            ->setParameter('now', new \DateTime())
            ->getQuery()
            ->getResult();
    }

}
