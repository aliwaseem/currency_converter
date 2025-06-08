<?php

//src/Controller/ConversionController.php

namespace App\Controller;

use App\Dto\ConversionDto;
use App\Exception\CurrencyNotFoundException;
use App\Service\CurrencyConverterService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\ObjectMapper\ObjectMapperInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;

/**
 * Controller for handling currency conversion requests.
 * 
 * This controller provides an endpoint for converting amounts between different currencies.
 * It handles request validation, currency conversion, and error responses.
 */
#[Route('/api/v1')]
class ConversionController extends AbstractController
{
    /**
     * @param CurrencyConverterService $converter Service for performing currency conversions
     * @param ObjectMapperInterface $objectMapper Service for mapping conversion results to DTOs
     */
    public function __construct(
        private readonly CurrencyConverterService $converter,
        private readonly ObjectMapperInterface $objectMapper
    ) {
    }

    /**
     * Converts an amount from one currency to another.
     * 
     * This endpoint accepts a POST request with JSON body containing:
     * - sourceCurrency: The currency code to convert from (e.g., "USD")
     * - destinationCurrency: The currency code to convert to (e.g., "EUR")
     * - sourceAmount: The amount to convert
     * 
     * @param Request $request The HTTP request object
     * 
     * @return JsonResponse A JSON response containing:
     *                      - On success: The converted amount and exchange rate
     *                      - On validation error: Details of the validation failures
     *                      - On currency not found: Error message
     *                      - On other errors: Generic error message
     * 
     * @throws CurrencyNotFoundException When either currency code is not supported
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
            return $this->json([
                'message' => $e->getMessage()
            ], Response::HTTP_NOT_FOUND);
        } catch (\Exception $e) {
            return $this->json([
                'message' => 'An unexpected error occurred',
                'error' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}