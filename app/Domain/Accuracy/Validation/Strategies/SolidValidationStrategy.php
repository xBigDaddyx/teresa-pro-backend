<?php

namespace App\Domain\Accuracy\Validation\Strategies;

use App\Domain\Accuracy\CartonBox\Entities\CartonBox;
use App\Domain\Accuracy\Validation\Entities\Item;

class SolidValidationStrategy implements ValidationStrategy
{
    public function validate(CartonBox $carton, Item $item): void
    {
        $cartonAttributes = $this->getAttributes($carton);
        $itemDetails = $item->getDetails();

        $itemSize = $itemDetails['Size'] ?? null;
        $itemColor = $itemDetails['Color'] ?? null;

        if (strcasecmp($cartonAttributes['Size'] ?? '', $itemSize) !== 0 ||
            strcasecmp($cartonAttributes['Color'] ?? '', $itemColor) !== 0) {
            throw new \Exception('Attribute Mismatch! The item attributes do not match the carton attributes.');
        }
    }

    public function getAttributes(CartonBox $carton): array
    {
        $details = $carton->getPackingList()?->getDetails() ?? [];
        return $details[0]['attributes'] ?? [];
    }
}
