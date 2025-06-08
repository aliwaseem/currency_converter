<?php

// src/Repository/ExchangeRateRepository.php

namespace App\Repository;

use App\Entity\Currency;
use App\Entity\ExchangeRate;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * Repository for ExchangeRate entity.
 * 
 * This repository provides methods to find and manage exchange rates in the system.
 * It extends ServiceEntityRepository to get basic CRUD operations.
 */
class ExchangeRateRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ExchangeRate::class);
    }

    /**
     * Find the current exchange rate for a currency.
     *
     * @param Currency $currency The currency to find the rate for
     * @return ExchangeRate|null The current exchange rate if found, null otherwise
     */
    public function findCurrentRate(Currency $currency): ?ExchangeRate
    {
        return $this->createQueryBuilder('r')
            ->where('r.currency = :currency')
            ->andWhere('r.validFrom <= :now')
            ->andWhere('r.validTo >= :now')
            ->setParameter('currency', $currency)
            ->setParameter('now', new \DateTime())
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Find any overlapping exchange rates for a currency within a date range.
     *
     * @param Currency $currency The currency to check for overlaps
     * @param \DateTime $from The start of the date range
     * @param \DateTime $to The end of the date range
     * @return array<ExchangeRate> Array of overlapping exchange rates
     */
    public function findOverlappingRates(Currency $currency, \DateTime $from, \DateTime $to): array
    {
        return $this->createQueryBuilder('r')
            ->where('r.currency = :currency')
            ->andWhere('r.validFrom <= :to')
            ->andWhere('r.validTo >= :from')
            ->setParameter('currency', $currency)
            ->setParameter('from', $from)
            ->setParameter('to', $to)
            ->getQuery()
            ->getResult();
    }

    /**
     * Find exchange rates for a currency within a date range.
     *
     * @param Currency $currency The currency to find rates for
     * @param \DateTime $from The start of the date range
     * @param \DateTime $to The end of the date range
     * @return array<ExchangeRate> Array of exchange rates in the date range
     */
    public function findRatesInDateRange(Currency $currency, \DateTime $from, \DateTime $to): array
    {
        return $this->createQueryBuilder('r')
            ->where('r.currency = :currency')
            ->andWhere('r.validFrom >= :from')
            ->andWhere('r.validTo <= :to')
            ->setParameter('currency', $currency)
            ->setParameter('from', $from)
            ->setParameter('to', $to)
            ->orderBy('r.validFrom', 'ASC')
            ->getQuery()
            ->getResult();
    }


}
