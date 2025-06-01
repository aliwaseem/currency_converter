<?php

namespace App\Tests\Controller;

use App\Controller\ConversionController;
use App\Dto\ConversionRequest;
use App\Service\CurrencyConverterService;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class ConversionControllerTest extends TestCase
{
    private ConversionController $controller;
    private CurrencyConverterService $converterService;
    private SerializerInterface $serializer;
    private ValidatorInterface $validator;
    private ContainerInterface $container;

    protected function setUp(): void
    {
        $this->converterService = $this->createMock(CurrencyConverterService::class);
        $this->serializer = $this->createMock(SerializerInterface::class);
        $this->validator = $this->createMock(ValidatorInterface::class);
        $this->container = $this->createMock(ContainerInterface::class);

        $this->controller = new ConversionController(
            $this->converterService,
            $this->serializer,
            $this->validator
        );
        $this->controller->setContainer($this->container);
    }

    public function testSuccessfulConversion(): void
    {
        // Mock request data
        $requestData = [
            'sourceCurrency' => 'USD',
            'destinationCurrency' => 'EUR',
            'sourceAmount' => 100.00
        ];

        // Mock DTO
        $dto = new ConversionRequest(
            sourceCurrency: 'USD',
            destinationCurrency: 'EUR',
            sourceAmount: 100.00
        );

        // Mock conversion result
        $conversionResult = [
            'source_currency' => 'USD',
            'destination_currency' => 'EUR',
            'source_amount' => 100.00,
            'destination_amount' => 92.00,
            'exchange_rate' => 0.92
        ];

        // Set up mocks
        $this->serializer->expects($this->once())
            ->method('deserialize')
            ->willReturn($dto);

        $this->validator->expects($this->once())
            ->method('validate')
            ->willReturn(new ConstraintViolationList());

        $this->converterService->expects($this->once())
            ->method('convert')
            ->willReturn($conversionResult);

        // Create request
        $request = new Request([], [], [], [], [], [], json_encode($requestData));

        // Execute controller
        $response = $this->controller->convert($request);
        $responseData = json_decode($response->getContent(), true);

        // Assert response
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('success', $responseData['status']);
        $this->assertEquals(200, $responseData['code']);
        $this->assertEquals($conversionResult, $responseData['data']);
    }

    public function testValidationError(): void
    {
        // Mock request data
        $requestData = [
            'sourceCurrency' => 'USD',
            'destinationCurrency' => 'EUR',
            'sourceAmount' => -100.00 // Invalid amount
        ];

        // Mock DTO
        $dto = new ConversionRequest(
            sourceCurrency: 'USD',
            destinationCurrency: 'EUR',
            sourceAmount: -100.00
        );

        // Mock validation violations
        $violations = new ConstraintViolationList([
            new ConstraintViolation(
                'Source amount must be positive',
                null,
                [],
                $dto,
                'sourceAmount',
                -100.00
            )
        ]);

        // Set up mocks
        $this->serializer->expects($this->once())
            ->method('deserialize')
            ->willReturn($dto);

        $this->validator->expects($this->once())
            ->method('validate')
            ->willReturn($violations);

        // Create request
        $request = new Request([], [], [], [], [], [], json_encode($requestData));

        // Execute controller
        $response = $this->controller->convert($request);
        $responseData = json_decode($response->getContent(), true);

        // Assert response
        $this->assertEquals(400, $response->getStatusCode());
        $this->assertEquals('error', $responseData['status']);
        $this->assertEquals(400, $responseData['code']);
        $this->assertEquals('Validation failed', $responseData['message']);
        $this->assertArrayHasKey('sourceAmount', $responseData['errors']);
    }

    public function testCurrencyNotFound(): void
    {
        // Mock request data
        $requestData = [
            'sourceCurrency' => 'USD',
            'destinationCurrency' => 'INVALID',
            'sourceAmount' => 100.00
        ];

        // Mock DTO
        $dto = new ConversionRequest(
            sourceCurrency: 'USD',
            destinationCurrency: 'INVALID',
            sourceAmount: 100.00
        );

        // Set up mocks
        $this->serializer->expects($this->once())
            ->method('deserialize')
            ->willReturn($dto);

        $this->validator->expects($this->once())
            ->method('validate')
            ->willReturn(new ConstraintViolationList());

        $this->converterService->expects($this->once())
            ->method('convert')
            ->willThrowException(new \App\Exception\CurrencyNotFoundException('Currency not found: USD or INVALID'));

        // Create request
        $request = new Request([], [], [], [], [], [], json_encode($requestData));

        // Execute controller
        $response = $this->controller->convert($request);
        $responseData = json_decode($response->getContent(), true);

        // Assert response
        $this->assertEquals(404, $response->getStatusCode());
        $this->assertEquals('error', $responseData['status']);
        $this->assertEquals(404, $responseData['code']);
        $this->assertEquals('Currency not found: USD or INVALID', $responseData['message']);
    }
} 