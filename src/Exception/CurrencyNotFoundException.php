<?php

//src/Exception/CurrencyNotFoundException.php

namespace App\Exception;

/**
 * Exception thrown when a requested currency code is not found in the exchange rates.
 * 
 * This exception is thrown by the CurrencyConverterService when:
 * - The source currency code is not supported
 * - The destination currency code is not supported
 * - Either currency code is not present in the exchange rates data
 * 
 * @see \App\Service\CurrencyConverterService
 */
class CurrencyNotFoundException extends \Exception {}


