<?php

//src/Controller/ConversionController.php

namespace App\Controller;

use App\Dto\ConversionRequest;
use App\Service\CurrencyConverterService;
use App\Exception\CurrencyNotFoundException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * Controller for handling currency conversion requests.
 * 
 * This controller provides an endpoint for converting amounts between different currencies.
 * It handles request validation, currency conversion, and error responses.
 */
class ConversionController extends AbstractController
{
    /**
     * @param CurrencyConverterService $currencyConverter Service for performing currency conversions
     * @param SerializerInterface $serializer Service for deserializing JSON requests
     * @param ValidatorInterface $validator Service for validating request data
     */
    public function __construct(
        private CurrencyConverterService $currencyConverter,
        private SerializerInterface $serializer,
        private ValidatorInterface $validator
    ) {}

    /**
     * Converts an amount from one currency to another.
     * 
     * This endpoint accepts a POST request with JSON body containing:
     * - sourceCurrency: The currency code to convert from (e.g., "USD")
     * - destinationCurrency: The currency code to convert to (e.g., "EUR")
     * - sourceAmount: The amount to convert
     * 
     * @param Request $request The HTTP request containing the conversion parameters
     * 
     * @return JsonResponse A JSON response containing:
     *                      - On success: The converted amount and exchange rate
     *                      - On validation error: Details of the validation failures
     *                      - On currency not found: Error message
     *                      - On other errors: Generic error message
     * 
     * @throws CurrencyNotFoundException When either currency code is not supported
     */
    #[Route('/api/convert', name: 'api_convert', methods: ['POST'])]
    public function convert(Request $request): JsonResponse
    {
        try {
            // Deserialize the request body into the DTO
            $dto = $this->serializer->deserialize(
                $request->getContent(),
                ConversionRequest::class,
                'json'
            );

            // Validate the DTO
            $violations = $this->validator->validate($dto);
            if (count($violations) > 0) {
                $errors = [];
                foreach ($violations as $violation) {
                    $errors[$violation->getPropertyPath()] = $violation->getMessage();
                }
                return $this->json([
                    'status' => 'error',
                    'code' => Response::HTTP_BAD_REQUEST,
                    'message' => 'Validation failed',
                    'errors' => $errors
                ], Response::HTTP_BAD_REQUEST);
            }

            // Perform the conversion
            $sourceCurrency = strtoupper($dto->sourceCurrency);
            $destinationCurrency = strtoupper($dto->destinationCurrency);
            $sourceAmount = $dto->sourceAmount;

            $result = $this->currencyConverter->convert(
                $sourceCurrency,
                $destinationCurrency,
                $sourceAmount
            );

            // Set the result in the DTO
            $dto->destinationAmount = $result['destination_amount'];
            $dto->exchangeRate = $result['exchange_rate'];

            return $this->json([
                'status' => 'success',
                'code' => Response::HTTP_OK,
                'data' => [
                    'source_currency' => $dto->sourceCurrency,
                    'destination_currency' => $dto->destinationCurrency,
                    'source_amount' => $dto->sourceAmount,
                    'destination_amount' => $dto->destinationAmount,
                    'exchange_rate' => $dto->exchangeRate
                ]
            ], Response::HTTP_OK);
        } catch (CurrencyNotFoundException $e) {
            return $this->json([
                'status' => 'error',
                'code' => Response::HTTP_NOT_FOUND,
                'message' => $e->getMessage()
            ], Response::HTTP_NOT_FOUND);
        } catch (\Throwable $e) {
            return $this->json([
                'status' => 'error',
                'code' => Response::HTTP_INTERNAL_SERVER_ERROR,
                'message' => 'An unexpected error occurred'
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }


}