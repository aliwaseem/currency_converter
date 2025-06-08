<?php

// src/Enum/AppEnum.php

namespace App\Enum;

/**
 * Application-wide constants and enumerations.
 * 
 * This enum contains various constants used throughout the application,
 * grouped by their purpose.
 */
enum AppEnum: int
{
    // Exchange rate precision
    case RATE_PRECISION = 7;

} 