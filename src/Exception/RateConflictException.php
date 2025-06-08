<?php

// src/Exception/RateConflictException.php

namespace App\Exception;

/**
 * Exception thrown when there is a conflict in exchange rate date ranges.
 * 
 * This exception is thrown in the following cases:
 * - A new exchange rate's date range overlaps with an existing rate for the same currency
 * - The overlap can be partial or complete
 * - The conflict is detected during rate creation
 * 
 * Used by:
 * - RateFixtures: When loading rates from CSV and detecting overlaps
 * 
 * 
 * @see \App\DataFixtures\RateFixtures
 */
class RateConflictException extends \Exception {}
