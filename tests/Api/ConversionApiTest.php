<?php

// src/Tests/Api/ConversionApiTest.php

namespace App\Tests\Api;

use App\Entity\ApiKey;
use App\Repository\ApiKeyRepository;
use Doctrine\ORM\EntityManagerInterface;
use GuzzleHttp\Client;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * API tests for the currency conversion endpoint.
 * 
 * This test suite verifies the behavior of the currency conversion API endpoint
 * by testing various scenarios including successful conversions, validation errors,
 * and error handling.
 * 
 * @property Client $client The HTTP client used for making API requests
 * @property string $apiKey The API key used for authentication
 */
class ConversionApiTest extends KernelTestCase
{
    private Client $client;
    private string $apiKey;
    private EntityManagerInterface $entityManager;

    /**
     * Set up the test environment before each test.
     * 
     * Initializes the HTTP client with the base URI and configuration
     * to handle API requests without throwing exceptions for error responses.
     * Creates a test API key for authentication.
     * 
     * @return void
     */
    protected function setUp(): void
    {
        // Load environment variables from .env
        if (file_exists(dirname(__DIR__, 2) . '/.env')) {
            (new \Symfony\Component\Dotenv\Dotenv())->bootEnv(dirname(__DIR__, 2) . '/.env');
        }
        self::bootKernel();
        $this->entityManager = self::getContainer()->get(EntityManagerInterface::class);

        $this->client = new Client([
            'base_uri' => 'http://localhost:80', 
            'http_errors' => false, // Don't throw exceptions for 4xx/5xx responses
        ]);

        // Create a test API key
        $apiKey = new ApiKey();
        $apiKey->setName('Test API Key');
        $this->entityManager->persist($apiKey);
        $this->entityManager->flush();
        $this->apiKey = $apiKey->getKey();
    }

    /**
     * Clean up after each test.
     * 
     * Removes the test API key from the database.
     * 
     * @return void
     */
    protected function tearDown(): void
    {
        $apiKeyRepository = $this->entityManager->getRepository(ApiKey::class);
        $apiKey = $apiKeyRepository->findActiveByKey($this->apiKey);
        if ($apiKey) {
            $this->entityManager->remove($apiKey);
            $this->entityManager->flush();
        }

        parent::tearDown();
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
            ],
            'headers' => [
                'X-API-Key' => $this->apiKey
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
            ],
            'headers' => [
                'X-API-Key' => $this->apiKey
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
            ],
            'headers' => [
                'X-API-Key' => $this->apiKey
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
            ],
            'headers' => [
                'X-API-Key' => $this->apiKey
            ]
        ]);

        $this->assertEquals(422, $response->getStatusCode());
        
        $data = json_decode($response->getBody(), true);
        $this->assertEquals('Validation failed', $data['message']);
        $this->assertArrayHasKey('destinationCurrency', $data['errors']);
        $this->assertArrayHasKey('sourceAmount', $data['errors']);
    }

    /**
     * Test unauthorized access without API key.
     * 
     * Verifies that requests without an API key are rejected with a 401 status code.
     * 
     * @return void
     */
    public function testUnauthorizedAccess(): void
    {
        $response = $this->client->post('/api/v1/convert', [
            'json' => [
                'sourceCurrency' => 'USD',
                'destinationCurrency' => 'EUR',
                'sourceAmount' => 100.00
            ]
            // No API key header
        ]);

        $this->assertEquals(401, $response->getStatusCode());
        
        $data = json_decode($response->getBody(), true);
        $this->assertEquals('API key is required', $data['message']);
    }

    /**
     * Test unauthorized access with invalid API key.
     * 
     * Verifies that requests with an invalid API key are rejected with a 401 status code.
     * 
     * @return void
     */
    public function testInvalidApiKey(): void
    {
        $response = $this->client->post('/api/v1/convert', [
            'json' => [
                'sourceCurrency' => 'USD',
                'destinationCurrency' => 'EUR',
                'sourceAmount' => 100.00
            ],
            'headers' => [
                'X-API-Key' => 'invalid-api-key'
            ]
        ]);

        $this->assertEquals(401, $response->getStatusCode());
        
        $data = json_decode($response->getBody(), true);
        $this->assertEquals('Invalid API key', $data['message']);
    }
} 