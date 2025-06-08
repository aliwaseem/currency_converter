<?php

// src/EventSubscriber/ApiKeySubscriber.php

namespace App\EventSubscriber;

use App\Repository\ApiKeyRepository;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

/**
 * Event subscriber for API key authentication.
 * 
 * This subscriber checks for the presence and validity of an API key
 * in the X-API-Key header of each request. It ensures that API requests
 * are authenticated using a valid API key.
 */
class ApiKeySubscriber implements EventSubscriberInterface
{
    /**
     * Constructor for ApiKeySubscriber.
     *
     * @param ApiKeyRepository $apiKeyRepository The repository for API keys.
     */
    public function __construct(
        private readonly ApiKeyRepository $apiKeyRepository
    ) {
    }

    /**
     * Returns the events this subscriber is subscribed to.
     *
     * @return array The events this subscriber is subscribed to.
     */
    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => 'onKernelRequest',
        ];
    }

    /**
     * Handles the kernel request event.
     *
     * This method checks if the request is for an API route and validates the API key.
     * If the API key is missing or invalid, it sets an appropriate response.
     *
     * @param RequestEvent $event The request event.
     */
    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();
        
        // Skip API key check for non-API routes
        if (!str_starts_with($request->getPathInfo(), '/api/')) {
            return;
        }

        $apiKey = $request->headers->get('X-API-Key');
        
        if (!$apiKey) {
            $event->setResponse(
                new JsonResponse(
                    ['message' => 'API key is required'],
                    Response::HTTP_UNAUTHORIZED
                )
            );
            return;
        }

        $key = $this->apiKeyRepository->findActiveByKey($apiKey);
        
        if (!$key) {
            $event->setResponse(
                new JsonResponse(
                    ['message' => 'Invalid API key'],
                    Response::HTTP_UNAUTHORIZED
                )
            );
            return;
        }

        // Update last used timestamp
        $key->updateLastUsed();
        $this->apiKeyRepository->getEntityManager()->flush();
    }
} 