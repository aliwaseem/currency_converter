<?php

// src/Controller/ConversionController.php

namespace App\Controller;

use App\Dto\ConversionDto;
use App\Exception\CurrencyNotFoundException;
use App\Service\CurrencyConverterService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;

/**
 * Controller for handling currency conversion requests.
 */
#[Route('/api/v1')]
class ConversionController extends AbstractController
{
    /**
     * @param CurrencyConverterService $converter Service for performing currency conversions
     */
    public function __construct(
        private readonly CurrencyConverterService $converter
    ) {
    }

    /**
     * Converts an amount from one currency to another.
     * 
     * @param ConversionDto $dto The conversion request containing:
     * - sourceCurrency: Currency code to convert from (e.g., "USD")
     * - destinationCurrency: Currency code to convert to (e.g., "EUR")
     * - sourceAmount: Amount to convert
     * 
     * @return JsonResponse
     * - 200: Success with conversion data including source currency, destination currency, 
     *        source amount, destination amount, and exchange rate
     * - 400: Invalid JSON or empty request
     * - 404: Currency not found
     * - 422: Validation error
     * - 500: Server error
     */
    #[Route('/convert', name: 'api_convert', methods: ['POST'])]
    public function convert(#[MapRequestPayload] ConversionDto $dto): JsonResponse
    {
        try {
            // Perform the conversion
            $result = $this->converter->convert(
                $dto->sourceCurrency,
                $dto->destinationCurrency,
                $dto->sourceAmount
            );

            // Update the DTO with the result values
            $dto->destinationAmount = $result['destination_amount'];
            $dto->exchangeRate = $result['exchange_rate'];

            return $this->json($dto, Response::HTTP_OK);

        } catch (CurrencyNotFoundException $e) {
            return $this->json(
                ['message' => $e->getMessage()],
                Response::HTTP_NOT_FOUND
            );

        } catch (\Exception $e) {
            return $this->json(
                [
                    'message' => 'An unexpected error occurred',
                    'error' => $e->getMessage()
                ],
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }
}