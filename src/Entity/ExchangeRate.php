<?php

// src/Entity/ExchangeRate.php

namespace App\Entity;

use App\Repository\ExchangeRateRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * Represents an exchange rate entity in the system.
 * 
 * This entity stores exchange rate information for currencies relative to GBP (British Pound).
 * Each exchange rate record includes:
 * - The currency it applies to
 * - The rate value (units of currency per 1 GBP)
 * - The validity period (from/to dates)
 * - Timestamps for record creation and updates
 * 
 * Relationships:
 * - Many-to-One with Currency: Each exchange rate belongs to one currency
 * 
 * Note: All rates are stored relative to GBP as the base currency.
 * 
 * @see Currency
 * @see ExchangeRateRepository
 */
#[ORM\Entity(repositoryClass: ExchangeRateRepository::class)]
class ExchangeRate
{
    /**
     * The unique identifier of the exchange rate.
     * 
     * This is an auto-generated primary key that uniquely identifies
     * each exchange rate record in the database.
     */
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    /**
     * The currency this exchange rate applies to.
     * 
     * This is a many-to-one relationship with the Currency entity,
     * as each currency can have multiple exchange rates over different
     * time periods.
     */
    #[ORM\ManyToOne(inversedBy: 'exchangeRates')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Currency $currency = null;

    /**
     * The exchange rate value in units per 1 GBP.
     * 
     * This represents how many units of the currency are equivalent to 1 GBP.
     * For example:
     * - If USD rate is 1.25, then 1 GBP = 1.25 USD
     * - If EUR rate is 1.15, then 1 GBP = 1.15 EUR
     * 
     * Stored as a decimal with 15 total digits and 8 decimal places
     * to ensure precise rate calculations.
     */
    #[ORM\Column(type: Types::DECIMAL, precision: 15, scale: 8)]
    private ?string $unitsPerGbp = null;

    /**
     * The start date and time when this exchange rate becomes valid.
     * 
     * This marks the beginning of the period during which this
     * exchange rate can be used for conversions.
     */
    #[ORM\Column]
    private ?\DateTime $validFrom = null;

    /**
     * The end date and time when this exchange rate expires.
     * 
     * This marks the end of the period during which this
     * exchange rate can be used for conversions.
     */
    #[ORM\Column]
    private ?\DateTime $validTo = null;

    /**
     * The timestamp when the exchange rate record was created.
     * 
     * This field is automatically set when a new exchange rate is persisted
     * to the database.
     */
    #[ORM\Column]
    private ?\DateTime $createdAt = null;

    /**
     * The timestamp when the exchange rate record was last updated.
     * 
     * This field is automatically updated whenever the exchange rate record
     * is modified.
     */
    #[ORM\Column]
    private ?\DateTime $updatedAt = null;

    /**
     * Get the unique identifier of the exchange rate.
     *
     * @return int|null The exchange rate ID
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * Get the currency this exchange rate applies to.
     *
     * @return Currency|null The associated currency
     */
    public function getCurrency(): ?Currency
    {
        return $this->currency;
    }

    /**
     * Set the currency this exchange rate applies to.
     *
     * @param Currency|null $currency The currency to associate with this rate
     * @return self
     */
    public function setCurrency(?Currency $currency): static
    {
        $this->currency = $currency;

        return $this;
    }

    /**
     * Get the exchange rate value in units per 1 GBP.
     *
     * @return string|null The exchange rate value
     */
    public function getUnitsPerGbp(): ?string
    {
        return $this->unitsPerGbp;
    }

    /**
     * Set the exchange rate value in units per 1 GBP.
     *
     * @param string $unitsPerGbp The exchange rate value
     * @return self
     * @throws \InvalidArgumentException If the rate is not a valid decimal number
     */
    public function setUnitsPerGbp(string $unitsPerGbp): static
    {
        $this->unitsPerGbp = $unitsPerGbp;

        return $this;
    }

    /**
     * Get the start date and time when this exchange rate becomes valid.
     *
     * @return \DateTime|null The validity start date and time
     */
    public function getValidFrom(): ?\DateTime
    {
        return $this->validFrom;
    }

    /**
     * Set the start date and time when this exchange rate becomes valid.
     *
     * @param \DateTime $validFrom The validity start date and time
     * @return self
     */
    public function setValidFrom(\DateTime $validFrom): static
    {
        $this->validFrom = $validFrom;

        return $this;
    }

    /**
     * Get the end date and time when this exchange rate expires.
     *
     * @return \DateTime|null The validity end date and time
     */
    public function getValidTo(): ?\DateTime
    {
        return $this->validTo;
    }

    /**
     * Set the end date and time when this exchange rate expires.
     *
     * @param \DateTime $validTo The validity end date and time
     * @return self
     */
    public function setValidTo(\DateTime $validTo): static
    {
        $this->validTo = $validTo;

        return $this;
    }

    /**
     * Get the creation timestamp of the exchange rate record.
     *
     * @return \DateTime|null The creation date and time
     */
    public function getCreatedAt(): ?\DateTime
    {
        return $this->createdAt;
    }

    /**
     * Set the creation timestamp of the exchange rate record.
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
     * Get the last update timestamp of the exchange rate record.
     *
     * @return \DateTime|null The last update date and time
     */
    public function getUpdatedAt(): ?\DateTime
    {
        return $this->updatedAt;
    }

    /**
     * Set the last update timestamp of the exchange rate record.
     *
     * @param \DateTime $updatedAt The last update date and time
     * @return self
     */
    public function setUpdatedAt(\DateTime $updatedAt): static
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }
}
