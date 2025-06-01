<?php

namespace App\Tests\Service;

use App\Exception\CurrencyNotFoundException;
use App\Service\CurrencyConverterService;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;

class CurrencyConverterServiceTest extends TestCase
{
    private string $testRatesFile;
    private Filesystem $filesystem;

    protected function setUp(): void
    {
        $this->filesystem = new Filesystem();
        $this->testRatesFile = sys_get_temp_dir() . '/test_rates.csv';
        
        // Create a test CSV file with sample rates
        $this->filesystem->dumpFile($this->testRatesFile, <<<CSV
            Currency Code,Currency units per �1
            USD,1.25
            EUR,1.15
            JPY,150.00
            GBP,1.00
            CAD,1.50
            CSV
        );
    }

    protected function tearDown(): void
    {
        // Clean up the test file
        $this->filesystem->remove($this->testRatesFile);
    }

    public function testSuccessfulConversion(): void
    {
        $service = new CurrencyConverterService($this->testRatesFile);
        
        $result = $service->convert('USD', 'EUR', 100.00);
        
        $this->assertEquals('USD', $result['source_currency']);
        $this->assertEquals('EUR', $result['destination_currency']);
        $this->assertEquals(100.00, $result['source_amount']);
        $this->assertEquals(92.00, $result['destination_amount']); // 100 * (1.15/1.25)
        $this->assertEqualsWithDelta(0.92, $result['exchange_rate'], 0.00001);
    }

    public function testCurrencyNotFound(): void
    {
        $service = new CurrencyConverterService($this->testRatesFile);
        
        $this->expectException(CurrencyNotFoundException::class);
        $this->expectExceptionMessage('Currency not found: XXX or ABC');
        
        $service->convert('XXX', 'ABC', 100.00);
    }

    public function testInvalidRatesFile(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Exchange rates file not found: /nonexistent/file.csv');
        
        new CurrencyConverterService('/nonexistent/file.csv');
    }

    public function testInvalidCsvFormat(): void
    {
        // Create a malformed CSV file
        $this->filesystem->dumpFile($this->testRatesFile, "Invalid,Format\nNo,Currency,Code");
        
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Required columns not found in CSV file');
        
        new CurrencyConverterService($this->testRatesFile);
    }
} 