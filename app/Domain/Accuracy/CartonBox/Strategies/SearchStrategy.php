<?php

namespace App\Domain\Accuracy\CartonBox\Strategies;

use App\Domain\Accuracy\CartonBox\Entities\CartonBox;

interface SearchStrategy
{
    /**
     * Searches for carton boxes based on provided criteria.
     *
     * @return CartonBox[]
     */
    public function search(string $barcode, ?string $po, ?string $sku): array;
}
