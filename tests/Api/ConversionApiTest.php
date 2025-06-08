<?php

// src/Tests/Api/ConversionApiTest.php

namespace App\Tests\Api;

use GuzzleHttp\Client;
use PHPUnit\Framework\TestCase;

/**
 * API tests for the currency conversion endpoint.
 * 
 * This test suite verifies the behavior of the currency conversion API endpoint
 * by testing various scenarios including successful conversions, validation errors,
 * and error handling.
 * 
 * @property Client $client The HTTP client used for making API requests
 */
class ConversionApiTest extends TestCase
{
    private Client $client;

    /**
     * Set up the test environment before each test.
     * 
     * Initializes the HTTP client with the base URI and configuration
     * to handle API requests without throwing exceptions for error responses.
     * 
     * @return void
     */
    protected function setUp(): void
    {
        $this->client = new Client([
            'base_uri' => 'http://localhost:80', 
            'http_errors' => false, // Don't throw exceptions for 4xx/5xx responses
        ]);
    }

    /**
     * Test successful currency conversion.
     * 
     * Verifies that a valid conversion request returns a 200 status code
     * and the response contains the expected data structure with correct
     * source and destination currencies, amounts, and exchange rate.
     * 
     * @return void
     */
    public function testSuccessfulConversion(): void
    {
        $response = $this->client->post('/api/v1/convert', [
            'json' => [
                'sourceCurrency' => 'USD',
                'destinationCurrency' => 'EUR',
                'sourceAmount' => 100.00
            ]
        ]);

        $this->assertEquals(200, $response->getStatusCode());
        
        $data = json_decode($response->getBody(), true);
        $this->assertEquals('USD', $data['sourceCurrency']);
        $this->assertEquals('EUR', $data['destinationCurrency']);
        $this->assertEquals(100.00, $data['sourceAmount']);
        $this->assertIsFloat($data['destinationAmount']);
        $this->assertIsFloat($data['exchangeRate']);
    }

    /**
     * Test validation error for negative amount.
     * 
     * Verifies that submitting a negative source amount returns a 422 status code
     * and the appropriate validation error message.
     * 
     * @return void
     */
    public function testValidationError(): void
    {
        $response = $this->client->post('/api/v1/convert', [
            'json' => [
                'sourceCurrency' => 'USD',
                'destinationCurrency' => 'EUR',
                'sourceAmount' => -100.00
            ]
        ]);

        $this->assertEquals(422, $response->getStatusCode());
        
        $data = json_decode($response->getBody(), true);
        $this->assertEquals('Validation failed', $data['message']);
        $this->assertArrayHasKey('sourceAmount', $data['errors']);
        $this->assertEquals('Source amount must be positive', $data['errors']['sourceAmount']);
    }

    /**
     * Test validation error for invalid currency code.
     * 
     * Verifies that submitting an invalid currency code returns a 422 status code
     * and the appropriate validation error message.
     * 
     * @return void
     */
    public function testInvalidCurrency(): void
    {
        $response = $this->client->post('/api/v1/convert', [
            'json' => [
                'sourceCurrency' => 'USD',
                'destinationCurrency' => 'INVALID',
                'sourceAmount' => 100.00
            ]
        ]);

        $this->assertEquals(422, $response->getStatusCode());
        
        $data = json_decode($response->getBody(), true);
        $this->assertEquals('Invalid destination currency code', $data['errors']['destinationCurrency']);
    }

    /**
     * Test validation error for missing required fields.
     * 
     * Verifies that submitting a request without required fields returns a 422 status code
     * and the appropriate validation error messages for each missing field.
     * 
     * @return void
     */
    public function testMissingRequiredFields(): void
    {
        $response = $this->client->post('/api/v1/convert', [
            'json' => [
                'sourceCurrency' => 'USD'
                // Missing destinationCurrency and sourceAmount
            ]
        ]);

        $this->assertEquals(422, $response->getStatusCode());
        
        $data = json_decode($response->getBody(), true);
        $this->assertEquals('Validation failed', $data['message']);
        $this->assertArrayHasKey('destinationCurrency', $data['errors']);
        $this->assertArrayHasKey('sourceAmount', $data['errors']);
    }
} 