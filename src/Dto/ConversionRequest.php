<?php
// src/Dto/ConversionRequest.php

namespace App\Dto;

use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

/**
 * Data Transfer Object for currency conversion requests.
 * 
 * This DTO represents the structure of a currency conversion request,
 * containing source and destination currencies, amounts, and exchange rate.
 * It includes validation constraints to ensure data integrity.
 */
final class ConversionRequest
{
    /**
     * @param string|null $sourceCurrency The source currency code (e.g., USD, EUR)
     * @param string|null $destinationCurrency The destination currency code (e.g., GBP, JPY)
     * @param float|null $sourceAmount The amount to convert from the source currency
     * @param float|null $destinationAmount The converted amount in the destination currency (calculated)
     * @param float|null $exchangeRate The exchange rate used for the conversion (calculated)
     */
    public function __construct(
        #[Assert\NotBlank(message: 'Source currency is required')]
        #[Assert\Currency(message: 'Invalid source currency code')]
        public ?string $sourceCurrency = null,

        #[Assert\NotBlank(message: 'Destination currency is required')]
        #[Assert\Currency(message: 'Invalid destination currency code')]
        public ?string $destinationCurrency = null,

        #[Assert\NotBlank(message: 'Source amount is required')]
        #[Assert\Positive(message: 'Source amount must be positive')]
        public ?float $sourceAmount = null,

        public ?float $destinationAmount = null,

        public ?float $exchangeRate = null
    ) {}
}
