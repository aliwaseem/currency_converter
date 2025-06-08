<?php

// src/Tests/Service/CurrencyConverterServiceTest.php

namespace App\Tests\Service;

use App\Entity\Currency;
use App\Entity\ExchangeRate;
use App\Exception\CurrencyNotFoundException;
use App\Repository\CurrencyRepository;
use App\Repository\ExchangeRateRepository;
use App\Service\CurrencyConverterService;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use DateTime;
use InvalidArgumentException;

/**
 * Unit tests for the CurrencyConverterService.
 * 
 * This test suite verifies the currency conversion logic, including:
 * - Rate calculations between different currencies
 * - GBP as source or destination currency
 * - Handling of different decimal places
 * - Error cases (currency not found, missing rates)
 * - Edge cases (large/small amounts, precision)
 * 
 * @property CurrencyRepository $currencyRepository Mock repository for currencies
 * @property ExchangeRateRepository $exchangeRateRepository Mock repository for exchange rates
 * @property LoggerInterface $logger Mock logger
 * @property CurrencyConverterService $service The service being tested
 */
class CurrencyConverterServiceTest extends TestCase
{
    private CurrencyRepository $currencyRepository;
    private ExchangeRateRepository $exchangeRateRepository;
    private LoggerInterface $logger;
    private CurrencyConverterService $service;

    /**
     * Set up the test environment before each test.
     * 
     * Initializes mock objects for dependencies and creates the service instance.
     * 
     * @return void
     */
    protected function setUp(): void
    {
        $this->currencyRepository = $this->createMock(CurrencyRepository::class);
        $this->exchangeRateRepository = $this->createMock(ExchangeRateRepository::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        
        $this->service = new CurrencyConverterService(
            $this->currencyRepository,
            $this->exchangeRateRepository,
            $this->logger
        );
    }

    /**
     * Test rate calculation between two non-GBP currencies.
     * 
     * Verifies that the rate is correctly calculated using GBP as the base currency.
     * 
     * @return void
     */
    public function testGetRateBetweenNonGbpCurrencies(): void
    {
        // Setup test data
        $usd = new Currency();
        $usd->setCode('USD');
        
        $eur = new Currency();
        $eur->setCode('EUR');
        
        $usdRate = new ExchangeRate();
        $usdRate->setUnitsPerGbp('1.25000000');
        
        $eurRate = new ExchangeRate();
        $eurRate->setUnitsPerGbp('1.15000000');
        
        // Configure mocks
        $this->currencyRepository->expects($this->exactly(2))
            ->method('findByCode')
            ->willReturnMap([
                ['USD', $usd],
                ['EUR', $eur]
            ]);
            
        $this->exchangeRateRepository->expects($this->exactly(2))
            ->method('findCurrentRate')
            ->willReturnMap([
                [$usd, $usdRate],
                [$eur, $eurRate]
            ]);
        
        // Test
        $rate = $this->service->getRate('USD', 'EUR');
        
        // Assert
        $this->assertEqualsWithDelta(0.92, $rate, 0.0000001); // 1.15/1.25
    }

    /**
     * Test rate calculation when GBP is the source currency.
     * 
     * Verifies that the rate is correctly calculated when converting from GBP.
     * 
     * @return void
     */
    public function testGetRateWithGbpAsSource(): void
    {
        // Setup test data
        $eur = new Currency();
        $eur->setCode('EUR');
        
        $eurRate = new ExchangeRate();
        $eurRate->setUnitsPerGbp('1.15000000');
        
        // Configure mocks
        $this->currencyRepository->expects($this->once())
            ->method('findByCode')
            ->with('EUR')
            ->willReturn($eur);
            
        $this->exchangeRateRepository->expects($this->once())
            ->method('findCurrentRate')
            ->with($eur)
            ->willReturn($eurRate);
        
        // Test
        $rate = $this->service->getRate('GBP', 'EUR');
        
        // Assert
        $this->assertEqualsWithDelta(1.15, $rate, 0.0000001);
    }

    /**
     * Test rate calculation when GBP is the destination currency.
     * 
     * Verifies that the rate is correctly calculated when converting to GBP.
     * 
     * @return void
     */
    public function testGetRateWithGbpAsDestination(): void
    {
        // Setup test data
        $usd = new Currency();
        $usd->setCode('USD');
        
        $usdRate = new ExchangeRate();
        $usdRate->setUnitsPerGbp('1.25000000');
        
        // Configure mocks
        $this->currencyRepository->expects($this->once())
            ->method('findByCode')
            ->with('USD')
            ->willReturn($usd);
            
        $this->exchangeRateRepository->expects($this->once())
            ->method('findCurrentRate')
            ->with($usd)
            ->willReturn($usdRate);
        
        // Test
        $rate = $this->service->getRate('USD', 'GBP');
        
        // Assert
        $this->assertEqualsWithDelta(0.8, $rate, 0.0000001); // 1/1.25
    }

    /**
     * Test conversion between currencies with different decimal places.
     * 
     * Verifies that amounts are correctly rounded according to the destination currency's
     * decimal places (e.g., JPY has 0 decimal places).
     * 
     * @return void
     */
    public function testConvertWithDifferentDecimalPlaces(): void
    {
        // Setup test data
        $usd = new Currency();
        $usd->setCode('USD');
        
        $jpy = new Currency();
        $jpy->setCode('JPY');
        
        $usdRate = new ExchangeRate();
        $usdRate->setUnitsPerGbp('1.25000000');
        
        $jpyRate = new ExchangeRate();
        $jpyRate->setUnitsPerGbp('150.00000000');
        
        // Configure mocks
        $this->currencyRepository->expects($this->exactly(2))
            ->method('findByCode')
            ->willReturnMap([
                ['USD', $usd],
                ['JPY', $jpy]
            ]);
            
        $this->exchangeRateRepository->expects($this->exactly(2))
            ->method('findCurrentRate')
            ->willReturnMap([
                [$usd, $usdRate],
                [$jpy, $jpyRate]
            ]);
        
        // Test
        $result = $this->service->convert('USD', 'JPY', 100.00);
        
        // Assert
        $this->assertEquals(12000, $result['destination_amount']); // JPY has 0 decimal places
        $this->assertEqualsWithDelta(120.0000000, $result['exchange_rate'], 0.0000001);
    }

    /**
     * Test handling of non-existent currency.
     * 
     * Verifies that a CurrencyNotFoundException is thrown when the source currency
     * does not exist in the database.
     * 
     * @return void
     */
    public function testCurrencyNotFound(): void
    {
        // Configure mocks
        $this->currencyRepository->expects($this->once())
            ->method('findByCode')
            ->with('XXX')
            ->willReturn(null);
        
        // Test and assert
        $this->expectException(CurrencyNotFoundException::class);
        $this->expectExceptionMessage('Source currency "XXX" not found');
        
        $this->service->getRate('XXX', 'USD');
    }

    /**
     * Test handling of missing exchange rate.
     * 
     * Verifies that a CurrencyNotFoundException is thrown when no current
     * exchange rate is found for a currency.
     * 
     * @return void
     */
    public function testNoCurrentRateFound(): void
    {
        // Setup test data
        $usd = new Currency();
        $usd->setCode('USD');
        
        // Configure mocks
        $this->currencyRepository->expects($this->exactly(2))
            ->method('findByCode')
            ->willReturnMap([
                ['USD', $usd],
                ['EUR', new Currency()]
            ]);
            
        $this->exchangeRateRepository->expects($this->once())
            ->method('findCurrentRate')
            ->with($usd)
            ->willReturn(null);
        
        // Test and assert
        $this->expectException(CurrencyNotFoundException::class);
        $this->expectExceptionMessage('No current rate found for USD');
        
        $this->service->getRate('USD', 'EUR');
    }

    /**
     * Test conversion with a large amount.
     * 
     * Verifies that the service can handle large amounts without precision loss
     * or floating-point overflow.
     * 
     * @return void
     */
    public function testConvertWithLargeAmount(): void
    {
        // Setup test data
        $usd = new Currency();
        $usd->setCode('USD');
        
        $eur = new Currency();
        $eur->setCode('EUR');
        
        $usdRate = new ExchangeRate();
        $usdRate->setUnitsPerGbp('1.25000000');
        
        $eurRate = new ExchangeRate();
        $eurRate->setUnitsPerGbp('1.15000000');
        
        // Configure mocks
        $this->currencyRepository->expects($this->exactly(2))
            ->method('findByCode')
            ->willReturnMap([
                ['USD', $usd],
                ['EUR', $eur]
            ]);
            
        $this->exchangeRateRepository->expects($this->exactly(2))
            ->method('findCurrentRate')
            ->willReturnMap([
                [$usd, $usdRate],
                [$eur, $eurRate]
            ]);
        
        // Test with a large amount
        $result = $this->service->convert('USD', 'EUR', 1000000.00);
        
        // Assert
        $this->assertEquals(920000.00, $result['destination_amount']);
        $this->assertEqualsWithDelta(0.9200000, $result['exchange_rate'], 0.0000001);
    }

    /**
     * Test conversion with a small amount.
     * 
     * Verifies that the service correctly handles small amounts and rounds
     * according to the destination currency's decimal places.
     * 
     * @return void
     */
    public function testConvertWithSmallAmount(): void
    {
        // Setup test data
        $usd = new Currency();
        $usd->setCode('USD');
        
        $jpy = new Currency();
        $jpy->setCode('JPY');
        
        $usdRate = new ExchangeRate();
        $usdRate->setUnitsPerGbp('1.25000000');
        
        $jpyRate = new ExchangeRate();
        $jpyRate->setUnitsPerGbp('150.00000000');
        
        // Configure mocks
        $this->currencyRepository->expects($this->exactly(2))
            ->method('findByCode')
            ->willReturnMap([
                ['USD', $usd],
                ['JPY', $jpy]
            ]);
            
        $this->exchangeRateRepository->expects($this->exactly(2))
            ->method('findCurrentRate')
            ->willReturnMap([
                [$usd, $usdRate],
                [$jpy, $jpyRate]
            ]);
        
        // Test with a small amount
        $result = $this->service->convert('USD', 'JPY', 0.01);
        
        // Assert
        $this->assertEquals(1, $result['destination_amount']); // JPY rounds to whole numbers
        $this->assertEqualsWithDelta(120.0000000, $result['exchange_rate'], 0.0000001);
    }

    /**
     * Test rate precision and consistency.
     * 
     * Verifies that the exchange rate remains consistent across multiple
     * conversions and that amounts are proportional to the source amount.
     * 
     * @return void
     */
    public function testGetRatePrecision(): void
    {
        // Setup test data
        $usd = new Currency();
        $usd->setCode('USD');
        
        $eur = new Currency();
        $eur->setCode('EUR');
        
        $usdRate = new ExchangeRate();
        $usdRate->setUnitsPerGbp('1.25000000');
        
        $eurRate = new ExchangeRate();
        $eurRate->setUnitsPerGbp('1.15000000');
        
        // Configure mocks
        $this->currencyRepository->expects($this->exactly(4))
            ->method('findByCode')
            ->willReturnMap([
                ['USD', $usd],
                ['EUR', $eur]
            ]);
            
        $this->exchangeRateRepository->expects($this->exactly(4))
            ->method('findCurrentRate')
            ->willReturnMap([
                [$usd, $usdRate],
                [$eur, $eurRate]
            ]);
        
        // Test multiple conversions with same rate
        $result1 = $this->service->convert('USD', 'EUR', 100.00);
        $result2 = $this->service->convert('USD', 'EUR', 200.00);
        
        // Assert rate is consistent
        $this->assertEqualsWithDelta($result1['exchange_rate'], $result2['exchange_rate'], 0.0000001);
        $this->assertEqualsWithDelta(0.9200000, $result1['exchange_rate'], 0.0000001);
        
        // Assert amounts are proportional
        $this->assertEqualsWithDelta(
            $result1['destination_amount'] * 2,
            $result2['destination_amount'],
            0.0000001
        );
    }

    /**
     * Test conversion with maximum float value.
     * 
     * Verifies that the service can handle PHP_FLOAT_MAX without overflow
     * and maintains precision.
     * 
     * @return void
     */
    public function testConvertWithMaximumFloatValue(): void
    {
        // Setup test data
        $usd = new Currency();
        $usd->setCode('USD');
        
        $eur = new Currency();
        $eur->setCode('EUR');
        
        $usdRate = new ExchangeRate();
        $usdRate->setUnitsPerGbp('1.25000000');
        
        $eurRate = new ExchangeRate();
        $eurRate->setUnitsPerGbp('1.15000000');
        
        // Configure mocks
        $this->currencyRepository->expects($this->exactly(2))
            ->method('findByCode')
            ->willReturnMap([
                ['USD', $usd],
                ['EUR', $eur]
            ]);
            
        $this->exchangeRateRepository->expects($this->exactly(2))
            ->method('findCurrentRate')
            ->willReturnMap([
                [$usd, $usdRate],
                [$eur, $eurRate]
            ]);
        
        // Test with PHP_FLOAT_MAX
        $result = $this->service->convert('USD', 'EUR', PHP_FLOAT_MAX);
        
        // Assert
        $this->assertIsFloat($result['destination_amount']);
        $this->assertTrue(is_finite($result['destination_amount']));
        $this->assertEqualsWithDelta(0.9200000, $result['exchange_rate'], 0.0000001);
    }
} 