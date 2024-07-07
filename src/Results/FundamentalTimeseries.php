<?php

declare(strict_types=1);

namespace Scheb\YahooFinanceApi\Results;

class FundamentalTimeseries implements \JsonSerializable
{
    /**
     * @var string
     */
    private $name;

    /**
     * @var float
     */
    private $value;

    /**
     * @var \DateTimeInterface
     */
    private $date;

    /**
     * @var string
     */
    private $periodType;

    public function __construct(string $name, float $value, \DateTimeInterface $date, string $periodType)
    {
        $this->name = $name;
        $this->value = $value;
        $this->date = $date;
        $this->periodType = $periodType;
    }

    /**
     * @return array
     */
    public function jsonSerialize()
    {
        return get_object_vars($this);
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getValue(): float
    {
        return $this->value;
    }

    public function getDate(): \DateTimeInterface
    {
        return $this->date;
    }

    public function getPeriodType(): string
    {
        return $this->periodType;
    }
}
