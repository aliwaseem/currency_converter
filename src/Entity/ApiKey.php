<?php

// src/Entity/ApiKey.php

namespace App\Entity;

use App\Repository\ApiKeyRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * Entity representing an API key for authentication.
 * 
 * This entity stores information about API keys, including their value,
 * name, active status, creation time, and last used time.
 * 
 * @property string $apiKey The API key value
 * @property string $name A descriptive name for the key
 * @property bool $isActive Whether the key is active
 * @property \DateTimeImmutable $createdAt When the key was created
 * @property \DateTimeImmutable|null $lastUsedAt When the key was last used
 */
#[ORM\Entity(repositoryClass: ApiKeyRepository::class)]
class ApiKey
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(name: 'api_key', type: 'string', length: 64, unique: true)]
    private string $apiKey;

    #[ORM\Column(length: 255)]
    private string $name;

    #[ORM\Column]
    private bool $isActive = true;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $lastUsedAt = null;

    /**
     * Constructor for ApiKey.
     * 
     * Initializes the API key with a random value and sets the creation time.
     */
    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->apiKey = bin2hex(random_bytes(32)); // Generate a random 64-character hex string
    }

    /**
     * Get the ID of the API key.
     * 
     * @return int|null The ID of the API key.
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * Get the API key value.
     * 
     * @return string The API key value.
     */
    public function getKey(): string
    {
        return $this->apiKey;
    }

    /**
     * Get the name of the API key.
     * 
     * @return string The name of the API key.
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Set the name of the API key.
     * 
     * @param string $name The name to set.
     * @return self
     */
    public function setName(string $name): self
    {
        $this->name = $name;
        return $this;
    }

    /**
     * Check if the API key is active.
     * 
     * @return bool Whether the API key is active.
     */
    public function isActive(): bool
    {
        return $this->isActive;
    }

    /**
     * Set the active status of the API key.
     * 
     * @param bool $isActive The active status to set.
     * @return self
     */
    public function setIsActive(bool $isActive): self
    {
        $this->isActive = $isActive;
        return $this;
    }

    /**
     * Get the creation time of the API key.
     * 
     * @return \DateTimeImmutable The creation time.
     */
    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    /**
     * Get the last used time of the API key.
     * 
     * @return \DateTimeImmutable|null The last used time.
     */
    public function getLastUsedAt(): ?\DateTimeImmutable
    {
        return $this->lastUsedAt;
    }

    /**
     * Update the last used time of the API key.
     * 
     * @return self
     */
    public function updateLastUsed(): self
    {
        $this->lastUsedAt = new \DateTimeImmutable();
        return $this;
    }
} 