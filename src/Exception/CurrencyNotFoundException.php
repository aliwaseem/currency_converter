<?php

//src/Exception/CurrencyNotFoundException.php

namespace App\Exception;

/**
 * Exception thrown when a currency code is invalid or not found.
 * 
 * This exception is thrown in the following cases:
 * - The currency code is empty
 * - The currency code is not a valid ISO 4217 code
 * - The currency code is not present in the exchange rates data
 * 
 * Used by:
 * - CurrencyConverterService: When converting between currencies
 * - RateFixtures: When loading currency data from CSV
 * 
 * @see \App\Service\CurrencyConverterService
 * @see \App\DataFixtures\RateFixtures
 */
class CurrencyNotFoundException extends \Exception {}