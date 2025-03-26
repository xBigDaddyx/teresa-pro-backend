<?php

namespace App\Domain\Accuracy\Validation\ValueObjects;

class AccuracyRule
{
    public const SOLID = 'SOLID';
    public const RATIO = 'RATIO';
    public const MIX = 'MIX';

    private $value;

    public function __construct($value)
    {
        $this->validate($value);
        $this->value = $value;
    }

    public function getValue(): string
    {
        return $this->value;
    }

    private function validate($value): void
    {
        $allowed = [self::SOLID, self::RATIO, self::MIX];
        if (!in_array($value, $allowed)) {
            throw new \InvalidArgumentException("Aturan tidak valid: $value");
        }
    }
}
