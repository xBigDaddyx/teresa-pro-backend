<?php

namespace App\Domain\Accuracy\CartonBox\ValueObjects;

class Status
{
    private $value;


    public const OPEN = 'OPEN';

    public const SEALED = 'SEALED';

    public function __construct($value)
    {
        $this->validate($value);
        $this->value = $value;
    }

    public function getValue() { return $this->value; }

    private function validate($value): void
    {
        $allowed = [self::OPEN,self::SEALED];
        if (!in_array($value, $allowed)) {
            throw new \InvalidArgumentException("Status tidak valid: $value");
        }
    }
}
