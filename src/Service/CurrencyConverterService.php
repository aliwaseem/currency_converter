<?php

// src/Service/CurrencyConverterService.php

namespace App\Service;
use App\Exception\CurrencyNotFoundException;

/**
 * Service for handling currency conversions using exchange rates from a CSV file.
 * 
 * This service provides functionality to:
 * - Load exchange rates from a CSV file
 * - Convert amounts between different currencies
 * - Calculate exchange rates between currency pairs
 * 
 * The CSV file should contain columns:
 * - "Currency Code": The ISO 4217 currency code
 * - "Currency units per £1": The exchange rate relative to GBP
 */
class CurrencyConverterService
{
    /**
     * @var array<string, float> Cache of currency codes to their exchange rates
     */
    private array $exchangeRates = [];

    /**
     * @param string $ratesFilePath Path to the CSV file containing exchange rates
     * 
     * @throws \RuntimeException If the exchange rates file cannot be read or opened
     */
    public function __construct(private string $ratesFilePath)
    {
        $this->loadExchangeRatesFromCsv();
    }

    /**
     * Loads exchange rates from the configured CSV file.
     * 
     * The CSV file should have a header row with columns:
     * - "Currency Code"
     * - "Currency units per £1"
     * 
     * @throws \RuntimeException If the file cannot be read, opened, or has invalid format
     */
    private function loadExchangeRatesFromCsv(): void
    {
        if (!is_readable($this->ratesFilePath)) {
            throw new \RuntimeException("Exchange rates file not found: " . $this->ratesFilePath);
        }

        $handle = fopen($this->ratesFilePath, 'r');

        if ($handle === false) {
            throw new \RuntimeException("Failed to open exchange rates file: " . $this->ratesFilePath);
        }

        $header = fgetcsv($handle);
        $currencyIndex = array_search('Currency Code', $header);
        $unitsIndex = array_search('Currency units per �1', $header);

        if ($currencyIndex === false || $unitsIndex === false) {
            throw new \RuntimeException("Required columns not found in CSV file");
        }

        while (($row = fgetcsv($handle)) !== false) {
            $code = strtoupper(trim($row[$currencyIndex]));
            $units = (float) str_replace(',', '', $row[$unitsIndex]);
            if ($units > 0) {  // Only add non-zero rates
                $this->exchangeRates[$code] = $units;
            }
        }

        fclose($handle);
    }

    /**
     * Gets the exchange rate between two currencies.
     * 
     * @param string $sourceCurrency The source currency code (e.g., "USD")
     * @param string $destinationCurrency The destination currency code (e.g., "EUR")
     * 
     * @return float The exchange rate from source to destination currency
     * 
     * @throws CurrencyNotFoundException If either currency code is not supported
     */
    public function getRate(string $sourceCurrency, string $destinationCurrency): float
    {
        if (!isset($this->exchangeRates[$sourceCurrency]) || !isset($this->exchangeRates[$destinationCurrency])) {
            throw new CurrencyNotFoundException("Currency not found: $sourceCurrency or $destinationCurrency");
        }

        return $this->exchangeRates[$destinationCurrency] / $this->exchangeRates[$sourceCurrency];
    }

    /**
     * Converts an amount from one currency to another.
     * 
     * @param string $sourceCurrency The source currency code (e.g., "USD")
     * @param string $destinationCurrency The destination currency code (e.g., "EUR")
     * @param float $sourceAmount The amount to convert
     * 
     * @return array{
     *     source_currency: string,
     *     destination_currency: string,
     *     source_amount: float,
     *     destination_amount: float,
     *     exchange_rate: float
     * } The conversion result including source and destination amounts and the exchange rate used
     * 
     * @throws CurrencyNotFoundException If either currency code is not supported
     */
    public function convert(string $sourceCurrency, string $destinationCurrency, float $sourceAmount): array
    {
        $exchangeRate = $this->getRate($sourceCurrency, $destinationCurrency);
        $destinationAmount = round($sourceAmount * $exchangeRate, 2);

        return [
            'source_currency' => $sourceCurrency,
            'destination_currency' => $destinationCurrency,
            'source_amount' => $sourceAmount,
            'destination_amount' => $destinationAmount,
            'exchange_rate' => $exchangeRate
        ];
    }
}