# Currency Converter

A simple PHP application that converts currencies using exchange rates stored in a database.

## API Endpoints

The application exposes the following API endpoint for currency conversion:

- **POST /api/v1/convert**
  - **Request Body:**
    ```json
    {
      "sourceCurrency": "USD",
      "destinationCurrency": "EUR",
      "sourceAmount": 100.00
    }
    ```
  - **Response:**
    ```json
    {
      "sourceCurrency": "USD",
      "destinationCurrency": "EUR",
      "sourceAmount": 100.00,
      "destinationAmount": 92.00,
      "exchangeRate": 0.92
    }
    ```

## Authentication

All API requests require an API key in the `X-API-Key` header.

Example:

```bash
curl -X POST http://localhost:8000/api/v1/convert \
  -H "Content-Type: application/json" \
  -H "X-API-Key: <your-api-key>" \
  -d '{"sourceCurrency":"USD","destinationCurrency":"EUR","sourceAmount":100}'
```

## Overview

This project provides a service to convert amounts between different currencies. It uses exchange rates stored in a database, which are loaded from a CSV file using fixtures.

## Features

- Convert amounts between different currencies.
- Load exchange rates from a CSV file into the database using fixtures.
- Handle exceptions for invalid currencies and file formats.
- API key authentication for all endpoints.

## Usage

1. Ensure your CSV file is formatted correctly with headers: `Currency Code,Currency units per £1`.
2. Load the data into the database using fixtures:
   ```bash
   php bin/console doctrine:fixtures:load
   ```
3. Use the `CurrencyConverterService` to perform conversions.
4. Include your API key in the `X-API-Key` header for all requests.

## Testing

Run the tests using PHPUnit:

```bash
./vendor/bin/phpunit
``` 