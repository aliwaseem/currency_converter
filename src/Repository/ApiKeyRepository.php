<?php

// src/Repository/ApiKeyRepository.php

namespace App\Repository;

use App\Entity\ApiKey;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * Repository for managing API keys.
 * 
 * This repository provides methods to interact with the API key entity,
 * including finding active API keys by their value.
 * 
 * @extends ServiceEntityRepository<ApiKey>
 */
class ApiKeyRepository extends ServiceEntityRepository
{
    /**
     * Constructor for ApiKeyRepository.
     *
     * @param ManagerRegistry $registry The registry for managing entity managers.
     */
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ApiKey::class);
    }

    /**
     * Find an active API key by its value.
     * 
     * @param string $key The API key value to find.
     * @return ApiKey|null The found API key or null if not found.
     */
    public function findActiveByKey(string $key): ?ApiKey
    {
        return $this->findOneBy(['apiKey' => $key, 'isActive' => true]);
    }
} 