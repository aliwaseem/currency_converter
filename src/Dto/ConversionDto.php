<?php

// src/Dto/ConversionDto.php

namespace App\Dto;

use Symfony\Component\Validator\Constraints as Assert;

/**
 * Data Transfer Object for currency conversion requests and responses.
 * 
 * This DTO serves two purposes:
 * 1. Request: Validates input data for currency conversion
 * 2. Response: Formats the conversion result with exchange rate
 * 
 * @property string $sourceCurrency The source currency code (e.g., "USD")
 * @property string $destinationCurrency The destination currency code (e.g., "EUR")
 * @property float $sourceAmount The amount to convert
 * @property float|null $destinationAmount The converted amount (set after conversion)
 * @property float|null $exchangeRate The exchange rate used (set after conversion)
 */
class ConversionDto
{
    /**
     * The source currency code.
     * Required for request, validated for format.
     * 
     * @var string
     */
    #[Assert\NotBlank(message: 'Source currency is required')]
    #[Assert\Currency(message: 'Invalid source currency code')]
    public string $sourceCurrency = '';

    /**
     * The destination currency code.
     * Required for request, validated for format and must differ from source.
     * 
     * @var string
     */
    #[Assert\NotBlank(message: 'Destination currency is required')]
    #[Assert\Currency(message: 'Invalid destination currency code')]
    #[Assert\Expression(
        "this.sourceCurrency !== this.destinationCurrency",
        message: 'Source and destination currencies must be different'
    )]
    public string $destinationCurrency = '';

    /**
     * The amount to convert.
     * Required for request, must be positive.
     * 
     * @var float
     */
    #[Assert\NotBlank(message: 'Source amount is required')]
    #[Assert\Type('float', message: 'Source amount must be a number')]
    #[Assert\Positive(message: 'Source amount must be positive')]
    public float $sourceAmount = 0.0;

    /**
     * The converted amount.
     * Set in response after successful conversion.
     * 
     * @var float|null
     */
    public ?float $destinationAmount = null;

    /**
     * The exchange rate used for conversion.
     * Set in response after successful conversion.
     * 
     * @var float|null
     */
    public ?float $exchangeRate = null;

    /**
     * Sets the source currency code and transforms it to uppercase.
     * 
     * @param string $sourceCurrency The source currency code
     * 
     * @return void
     */
    public function setSourceCurrency(string $sourceCurrency): void
    {
        $this->sourceCurrency = strtoupper($sourceCurrency);
    }

    /**
     * Sets the destination currency code and transforms it to uppercase.
     * 
     * @param string $destinationCurrency The destination currency code
     * 
     * @return void
     */
    public function setDestinationCurrency(string $destinationCurrency): void
    {
        $this->destinationCurrency = strtoupper($destinationCurrency);
    }
} 