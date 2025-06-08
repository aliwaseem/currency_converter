<?php

// src/Entity/Currency.php

namespace App\Entity;

use App\Repository\CurrencyRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * Represents a currency entity in the system.
 * 
 * This entity stores information about different currencies including their codes,
 * names, and associated exchange rates. Each currency is uniquely identified by its
 * three-letter ISO 4217 code (e.g., USD, EUR, GBP).
 * 
 * The entity maintains:
 * - Basic currency information (code, name)
 * - Timestamps for record creation and updates
 * - A collection of exchange rates associated with this currency
 * 
 * Relationships:
 * - One-to-Many with ExchangeRate: A currency can have multiple exchange rates
 *   over different time periods
 * 
 * @see ExchangeRate
 * @see CurrencyRepository
 */
#[ORM\Entity(repositoryClass: CurrencyRepository::class)]
class Currency
{
    /**
     * The unique identifier of the currency.
     * 
     * This is an auto-generated primary key that uniquely identifies
     * each currency record in the database.
     */
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    /**
     * The three-letter currency code following ISO 4217 standard.
     * 
     * Examples:
     * - USD (United States Dollar)
     * - EUR (Euro)
     * - GBP (British Pound)
     * 
     * @var string|null
     */
    #[ORM\Column(length: 3)]
    private ?string $code = null;

    /**
     * The full name of the currency.
     * 
     * This is the official name of the currency as defined by ISO 4217.
     * Examples:
     * - "United States Dollar" for USD
     * - "Euro" for EUR
     * - "British Pound" for GBP
     * 
     * @var string|null
     */
    #[ORM\Column(length: 100)]
    private ?string $name = null;

    /**
     * The timestamp when the currency record was created.
     * 
     * This field is automatically set when a new currency is persisted
     * to the database.
     * 
     * @var \DateTime|null
     */
    #[ORM\Column]
    private ?\DateTime $createdAt = null;

    /**
     * The timestamp when the currency record was last updated.
     * 
     * This field is automatically updated whenever the currency record
     * is modified.
     * 
     * @var \DateTime|null
     */
    #[ORM\Column]
    private ?\DateTime $updatedAt = null;

    /**
     * Collection of exchange rates associated with this currency.
     * 
     * This is a one-to-many relationship with the ExchangeRate entity,
     * allowing a currency to have multiple exchange rates over different
     * time periods.
     * 
     * @var Collection<int, ExchangeRate>
     */
    #[ORM\OneToMany(targetEntity: ExchangeRate::class, mappedBy: 'currency')]
    private Collection $exchangeRates;

    /**
     * Get the unique identifier of the currency.
     *
     * @return int|null The currency ID
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * Get the currency code (e.g., USD, EUR).
     *
     * @return string|null The three-letter currency code
     */
    public function getCode(): ?string
    {
        return $this->code;
    }

    /**
     * Set the currency code.
     *
     * @param string $code The three-letter currency code
     * @return self
     * @throws \InvalidArgumentException If the code is not a valid ISO 4217 currency code
     */
    public function setCode(string $code): static
    {
        $this->code = $code;

        return $this;
    }

    /**
     * Get the full name of the currency.
     *
     * @return string|null The currency name
     */
    public function getName(): ?string
    {
        return $this->name;
    }

    /**
     * Set the full name of the currency.
     *
     * @param string $name The currency name
     * @return self
     */
    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get the creation timestamp of the currency record.
     *
     * @return \DateTime|null The creation date and time
     */
    public function getCreatedAt(): ?\DateTime
    {
        return $this->createdAt;
    }

    /**
     * Set the creation timestamp of the currency record.
     *
     * @param \DateTime $createdAt The creation date and time
     * @return self
     */
    public function setCreatedAt(\DateTime $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    /**
     * Get the last update timestamp of the currency record.
     *
     * @return \DateTime|null The last update date and time
     */
    public function getUpdatedAt(): ?\DateTime
    {
        return $this->updatedAt;
    }

    /**
     * Set the last update timestamp of the currency record.
     *
     * @param \DateTime $updatedAt The last update date and time
     * @return self
     */
    public function setUpdatedAt(\DateTime $updatedAt): static
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    /**
     * Get all exchange rates associated with this currency.
     *
     * @return Collection<int, ExchangeRate> Collection of exchange rates
     */
    public function getExchangeRates(): Collection
    {
        return $this->exchangeRates;
    }

    /**
     * Add an exchange rate to this currency.
     *
     * @param ExchangeRate $exchangeRate The exchange rate to add
     * @return self
     */
    public function addExchangeRate(ExchangeRate $exchangeRate): static
    {
        if (!$this->exchangeRates->contains($exchangeRate)) {
            $this->exchangeRates->add($exchangeRate);
            $exchangeRate->setCurrency($this);
        }

        return $this;
    }

    /**
     * Remove an exchange rate from this currency.
     *
     * @param ExchangeRate $exchangeRate The exchange rate to remove
     * @return self
     */
    public function removeExchangeRate(ExchangeRate $exchangeRate): static
    {
        if ($this->exchangeRates->removeElement($exchangeRate)) {
            // set the owning side to null (unless already changed)
            if ($exchangeRate->getCurrency() === $this) {
                $exchangeRate->setCurrency(null);
            }
        }

        return $this;
    }
}
