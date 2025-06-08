<?php

// src/DataFixtures/RateFixtures.php

declare(strict_types=1);

namespace App\DataFixtures;

use App\Entity\Currency;
use App\Entity\ExchangeRate;
use App\Exception\CurrencyNotFoundException;
use App\Exception\RateConflictException;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\ORM\NoResultException;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\Intl\Currencies;
use League\Csv\Reader;
use DateTime;
use DateTimeImmutable;
use RuntimeException;


/**
 * Fixture class for loading exchange rates from a CSV file.
 * 
 * This fixture loads currency exchange rates from a CSV file specified in the FOREX_DATA_PATH
 * environment variable. It handles currency validation, rate conflict detection, and proper
 * date range management.
 * 
 * Note: Currently uses a temporary fix with June 2025 dates for testing purposes.
 * To switch back to using dates from CSV, set USE_TEMP_DATES to false.
 */
class RateFixtures extends Fixture
{
    /**
     * Flag to control whether to use temporary fixed dates or dates from CSV
     */
    private const USE_TEMP_DATES = true;

    /**
     * Loads exchange rate data from CSV into the database.
     * 
     * The method:
     * 1. Reads the CSV file from the path specified in FOREX_DATA_PATH
     * 2. Groups rates by currency code and date range
     * 3. Validates currency codes
     * 4. Checks for rate conflicts
     * 5. Creates or updates currency and rate records
     *
     * @param ObjectManager $manager The Doctrine object manager
     * @throws \RuntimeException If CSV file is not found
     * @throws CurrencyNotFoundException If currency code is invalid or empty
     * @throws RateConflictException If rate conflicts are detected
     */
    public function load(ObjectManager $manager): void
    {   
        // Get CSV file from environment variable
        $csvFilePath = $_ENV['FOREX_DATA_PATH'] ?? '';
        if (!$csvFilePath || !file_exists($csvFilePath)) {
            throw new \RuntimeException('CSV file not found at path: ' . $csvFilePath);
        }

        // Initialize CSV reader
        $csv = Reader::createFromPath($csvFilePath, 'r');
        $csv->setHeaderOffset(0);

        // Set date range for all rates (temporary fix for testing)
        // as the dates from the csv file are from Jan 2022 and our 
        // application is designed to serve the current exchange rate.
        $tempStartDate = new DateTimeImmutable('2025-06-01 00:00:00');
        $tempEndDate = new DateTimeImmutable('2025-06-30 23:59:59');
        $now = DateTime::createFromImmutable(new DateTimeImmutable());

        // Group rates by currency code and date range
        $currencyGroups = [];
        foreach ($csv as $row) {
            $currencyCode = trim($row['Currency Code']);
            if (empty($currencyCode)) {
                throw new CurrencyNotFoundException(
                    sprintf('Row: %s Currency code cannot be empty', json_encode($row))
                );
            }

            // Use either temporary dates or dates from CSV
            if (self::USE_TEMP_DATES) {
                $startDate = $tempStartDate;
                $endDate = $tempEndDate;
            } else {
                $startDate = $this->parseCsvDate($row['Valid From'], false);
                $endDate = $this->parseCsvDate($row['Valid To'], true);
            }

            $groupKey = sprintf('%s_%s_%s', 
                $currencyCode,
                $startDate->format('Y-m-d'),
                $endDate->format('Y-m-d')
            );

            if (!isset($currencyGroups[$groupKey])) {
                $currencyGroups[$groupKey] = [
                    'currency_code' => $currencyCode,
                    'currency_name' => trim($row['Currency']),
                    'start_date' => $startDate,
                    'end_date' => $endDate,
                    'rate' => $row['Currency units per �1'] // Use exact column name from CSV
                ];
            }
        }

        // Process each currency group
        foreach ($currencyGroups as $group) {
            // Validate currency code
            if (!Currencies::exists($group['currency_code'])) {
                throw new CurrencyNotFoundException(
                    sprintf('Invalid currency code "%s". This is not a valid ISO 4217 currency code.', $group['currency_code'])
                );
            }

            // Create or get currency
            $currency = $this->getOrCreateCurrency($group, $manager, $now);

            // Check for overlapping rates
            $qb = $manager->getRepository(ExchangeRate::class)
                ->createQueryBuilder('er')
                ->where('er.currency = :currency')
                ->andWhere('er.validFrom <= :newEnd')
                ->andWhere('er.validTo >= :newStart')
                ->setParameter('currency', $currency)
                ->setParameter('newStart', $group['start_date'])
                ->setParameter('newEnd', $group['end_date'])
                ->setMaxResults(1);

            try {
                $existing = $qb->getQuery()->getOneOrNullResult();
                if ($existing) {
                    throw new RateConflictException(sprintf(
                        'Conflict detected for %s: existing rate (ID=%d) from %s to %s overlaps new CSV range %s to %s.',
                        $group['currency_code'],
                        $existing->getId(),
                        $existing->getValidFrom()->format('Y-m-d H:i:s'),
                        $existing->getValidTo()->format('Y-m-d H:i:s'),
                        $group['start_date']->format('Y-m-d H:i:s'),
                        $group['end_date']->format('Y-m-d H:i:s')
                    ));
                }

                $this->createExchangeRate($group, $currency, $manager, $now);
            } catch (NoResultException $e) {
                // No overlap found - safe to create new rate
                $this->createExchangeRate($group, $currency, $manager, $now);
            }
        }

        $manager->flush();
    }

    /**
     * Gets an existing currency or creates a new one.
     * 
     * @param array $group The currency group data containing code and name
     * @param ObjectManager $manager The Doctrine object manager
     * @param DateTime $now The current timestamp
     * 
     * @return Currency The existing or newly created currency entity
     */
    private function getOrCreateCurrency(array $group, ObjectManager $manager, DateTime $now): Currency
    {
        $currency = $manager->getRepository(Currency::class)->findOneBy(['code' => $group['currency_code']]);
        if (!$currency) {
            $currency = new Currency();
            $currency->setCode($group['currency_code']);
            $currency->setName($group['currency_name']);
            $currency->setCreatedAt($now);
            $currency->setUpdatedAt($now);           
            $manager->persist($currency);
            $manager->flush();
        }
        return $currency;
    }

    /**
     * Creates a new exchange rate for a currency.
     * 
     * @param array $group The rate group data containing dates and rate value
     * @param Currency $currency The currency entity to create the rate for
     * @param ObjectManager $manager The Doctrine object manager
     * @param DateTime $now The current timestamp
     */
    private function createExchangeRate(array $group, Currency $currency, ObjectManager $manager, DateTime $now): void
    {
        $rate = new ExchangeRate();
        $rate->setCurrency($currency);
        $rate->setUnitsPerGbp($group['rate']);
        $rate->setValidFrom(DateTime::createFromImmutable($group['start_date']));
        $rate->setValidTo(DateTime::createFromImmutable($group['end_date']));
        $rate->setCreatedAt($now);
        $rate->setUpdatedAt($now);
        $manager->persist($rate);
    }

    /**
     * Parse a raw date string into DateTimeImmutable.
     *
     * Supports either:
     * - "YYYY-MM-DD"           - returns YYYY-MM-DD 00:00:00 (for $isEnd = false)
     *                           - returns YYYY-MM-DD 23:59:59 (for $isEnd = true)
     * - "YYYY-MM-DD HH:MM:SS"  - returns exactly that timestamp, regardless of $isEnd.
     *
     * @param string $rawDate  The raw date field from CSV
     * @param bool   $isEnd    If true and $rawDate has no time, set time to 23:59:59; otherwise 00:00:00
     * @return \DateTimeImmutable
     * @throws \InvalidArgumentException if neither format matches
     */
    private function parseCsvDate(string $rawDate, bool $isEnd = false): \DateTimeImmutable
    {
        $rawDate = trim($rawDate);
   
        // 1) Try parsing with full "Y-m-d H:i:s"
        $dt = \DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $rawDate);
        if ($dt !== false) {
            return $dt;
        }

        // 2) If that fails, try just "d/m/Y"
        $dt = \DateTimeImmutable::createFromFormat('d/m/Y', $rawDate);
        if ($dt !== false) {
            return $isEnd 
                ? $dt->setTime(23, 59, 59) 
                : $dt->setTime(0, 0, 0);
        }

        // 3) Neither format matched - throw
        throw new \InvalidArgumentException(
            sprintf("Unrecognized date format: '%s'. Expected 'Y-m-d' or 'Y-m-d H:i:s'.", $rawDate)
        );
    }
}