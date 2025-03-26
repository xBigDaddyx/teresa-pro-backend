<?php

namespace App\Domain\Accuracy\Validation\Strategies;

use App\Domain\Accuracy\CartonBox\Entities\CartonBox;
use App\Domain\Accuracy\Validation\Entities\Item;

interface ValidationStrategy
{
    /**
     * Validates an item against a carton box based on the strategy.
     *
     * @throws \Exception if validation fails
     */
    public function validate(CartonBox $carton, Item $item): void;

    /**
     * Retrieves the attributes used for validation from the carton.
     */
    public function getAttributes(CartonBox $carton): array;
}
