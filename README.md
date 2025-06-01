# Currency Converter

A simple PHP application that converts currencies using exchange rates stored in a CSV file.

## API Endpoints

The application exposes the following API endpoint for currency conversion:

- **POST /api/convert**
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
      "source_currency": "USD",
      "destination_currency": "EUR",
      "source_amount": 100.00,
      "destination_amount": 92.00,
      "exchange_rate": 0.92
    }
    ```

## Sample Request

To convert 100 USD to EUR, send a POST request to `/api/convert` with the following JSON body:

```json
{
  "sourceCurrency": "USD",
  "destinationCurrency": "EUR",
  "sourceAmount": 100.00
}
```

This will return the converted amount and exchange rate in the response.

## Overview

This project provides a service to convert amounts between different currencies. It reads exchange rates from a CSV file and performs the conversion based on the provided source and destination currencies.

## Features

- Convert amounts between different currencies.
- Read exchange rates from a CSV file.
- Handle exceptions for invalid currencies and file formats.

## Usage

1. Ensure your CSV file is formatted correctly with headers: `Currency Code,Currency units per £1`.
2. Use the `CurrencyConverterService` to perform conversions.

## Testing

Run the tests using PHPUnit:

```bash
./vendor/bin/phpunit
``` 