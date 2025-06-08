<?php

// src/Service/CurrencyConverterService.php

namespace App\Service;

use App\Entity\Currency;
use App\Entity\ExchangeRate;
use App\Enum\AppEnum;
use App\Exception\CurrencyNotFoundException;
use App\Repository\CurrencyRepository;
use App\Repository\ExchangeRateRepository;
use Psr\Log\LoggerInterface;
use Symfony\Component\Intl\Currencies;
use InvalidArgumentException;

/**
 * Service for handling currency conversions using exchange rates from the database.
 * 
 * This service provides methods to convert amounts between different currencies
 * using exchange rates stored in the database.
 */
class CurrencyConverterService
{
    public function __construct(
        private readonly CurrencyRepository $currencyRepository,
        private readonly ExchangeRateRepository $exchangeRateRepository,
        private readonly LoggerInterface $logger
    ) {
    }

    /**
     * Get the exchange rate between two currencies.
     *
     * @param string $sourceCurrencyCode The source currency code
     * @param string $destinationCurrencyCode The destination currency code
     * @return float The exchange rate
     * @throws CurrencyNotFoundException If either currency is not found
     */
    public function getRate(string $sourceCurrencyCode, string $destinationCurrencyCode): float
    {
        $this->logger->info('Getting exchange rate', [
            'source' => $sourceCurrencyCode,
            'destination' => $destinationCurrencyCode,
            'time' => (new \DateTime())->format('Y-m-d H:i:s')
        ]);

        // Special handling for GBP
        if ($sourceCurrencyCode === 'GBP') {
            // Get destination currency
            $destinationCurrency = $this->currencyRepository->findByCode($destinationCurrencyCode);
            if (!$destinationCurrency) {
                throw new CurrencyNotFoundException(sprintf('Destination currency "%s" not found', $destinationCurrencyCode));
            }

            $destinationRate = $this->exchangeRateRepository->findCurrentRate($destinationCurrency);
            if (!$destinationRate) {
                throw new CurrencyNotFoundException(sprintf('No current rate found for %s', $destinationCurrencyCode));
            }
            return (float) $destinationRate->getUnitsPerGbp();
        }

        if ($destinationCurrencyCode === 'GBP') {
            // Get source currency
            $sourceCurrency = $this->currencyRepository->findByCode($sourceCurrencyCode);
            if (!$sourceCurrency) {
                throw new CurrencyNotFoundException(sprintf('Source currency "%s" not found', $sourceCurrencyCode));
            }

            $sourceRate = $this->exchangeRateRepository->findCurrentRate($sourceCurrency);
            if (!$sourceRate) {
                throw new CurrencyNotFoundException(sprintf('No current rate found for %s', $sourceCurrencyCode));
            }
            return 1 / (float) $sourceRate->getUnitsPerGbp();
        }

        // Get source currency
        $sourceCurrency = $this->currencyRepository->findByCode($sourceCurrencyCode);
        if (!$sourceCurrency) {
            throw new CurrencyNotFoundException(sprintf('Source currency "%s" not found', $sourceCurrencyCode));
        }

        // Get destination currency
        $destinationCurrency = $this->currencyRepository->findByCode($destinationCurrencyCode);
        if (!$destinationCurrency) {
            throw new CurrencyNotFoundException(sprintf('Destination currency "%s" not found', $destinationCurrencyCode));
        }

        // Get rates for both currencies
        $sourceRate = $this->exchangeRateRepository->findCurrentRate($sourceCurrency);
        if (!$sourceRate) {
            throw new CurrencyNotFoundException(sprintf('No current rate found for %s', $sourceCurrencyCode));
        }

        $destinationRate = $this->exchangeRateRepository->findCurrentRate($destinationCurrency);
        if (!$destinationRate) {
            throw new CurrencyNotFoundException(sprintf('No current rate found for %s', $destinationCurrencyCode));
        }

        // Calculate cross rate
        return (float) $destinationRate->getUnitsPerGbp() / (float) $sourceRate->getUnitsPerGbp();
    }

    /**
     * Convert an amount from one currency to another.
     * 
     * @param string $sourceCurrencyCode The source currency code
     * @param string $destinationCurrencyCode The destination currency code
     * @param float $amount The amount to convert
     * @return array{destination_amount: float, exchange_rate: float} Array containing the converted amount and the exchange rate used
     * @throws CurrencyNotFoundException If either currency is not found
     * @throws InvalidArgumentException If the amount is negative
     */
    public function convert(string $sourceCurrencyCode, string $destinationCurrencyCode, float $amount): array
    {
        if ($amount < 0) {
            throw new InvalidArgumentException('Amount must be positive');
        }

        // Get the raw rate and round it to display precision
        $rate = round($this->getRate($sourceCurrencyCode, $destinationCurrencyCode), AppEnum::RATE_PRECISION->value);
        
        // Calculate destination amount using the rounded rate
        $destinationAmount = $amount * $rate;
        
        // Get the number of decimal places for the destination currency
        $decimalPlaces = Currencies::getFractionDigits($destinationCurrencyCode);
        
        // Format the destination amount according to the currency's decimal places
        $destinationAmount = round($destinationAmount, $decimalPlaces);

        return [
            'destination_amount' => $destinationAmount,
            'exchange_rate' => $rate
        ];
    }
}