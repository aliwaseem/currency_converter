<?php

// src/EventSubscriber/PayloadErrorEventSubscriber.php

namespace App\EventSubscriber;

use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\Validator\Exception\ValidationFailedException;
use Symfony\Component\Serializer\Exception\NotNormalizableValueException;
use Symfony\Component\OptionsResolver\Exception\MissingOptionsException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

/**
 * Event subscriber for handling validation and deserialization errors in HTTP requests.
 * 
 * This subscriber catches validation exceptions and formats them into a consistent
 * JSON response structure for API endpoints.
 */
class PayloadErrorEventSubscriber implements EventSubscriberInterface
{
    /**
     * Returns the events this subscriber wants to listen to.
     * 
     * @return array<string, string> The event names to listen to
     */
    public static function getSubscribedEvents(): array
    {
        return [
            ExceptionEvent::class => 'onExceptionEvent'
        ];
    }

    /**
     * Handles validation exceptions and formats them into JSON responses.
     * 
     * @param ExceptionEvent $event The exception event
     */
    public function onExceptionEvent(ExceptionEvent $event): void
    {
        $throwable = $event->getThrowable();

        // Handle invalid JSON
        if ($throwable instanceof NotNormalizableValueException) {
            $event->setResponse(
                new JsonResponse(
                    ['message' => 'Invalid JSON'],
                    Response::HTTP_BAD_REQUEST
                )
            );
            return;
        }

        // Handle missing required fields
        if ($throwable instanceof MissingOptionsException || 
            $throwable instanceof BadRequestHttpException) {
            $event->setResponse(
                new JsonResponse(
                    ['message' => 'Invalid or missing data in request'],
                    Response::HTTP_UNPROCESSABLE_ENTITY
                )
            );
            return;
        }

        // Handle validation errors
        if ($throwable instanceof HttpExceptionInterface && 
            $throwable->getPrevious() instanceof ValidationFailedException) {
            $validationException = $throwable->getPrevious();
            $errorMessages = $this->formatValidationErrors($validationException);

            $event->setResponse(
                new JsonResponse(
                    [
                        'message' => 'Validation failed',
                        'errors' => $errorMessages
                    ],
                    Response::HTTP_UNPROCESSABLE_ENTITY
                )
            );
            return;
        }
    }

    /**
     * Formats validation errors into a structured array.
     * 
     * @param ValidationFailedException $exception The validation exception
     * 
     * @return array<string, string> The formatted error messages
     */
    private function formatValidationErrors(ValidationFailedException $exception): array
    {
        $errorMessages = [];

        foreach ($exception->getViolations() as $violation) {
            $errorMessages[$violation->getPropertyPath()] = $violation->getMessage();
        }

        return $errorMessages;
    }
} 