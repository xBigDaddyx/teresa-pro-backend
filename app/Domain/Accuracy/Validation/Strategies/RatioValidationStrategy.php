<?php

namespace App\Domain\Accuracy\Validation\Strategies;

use App\Domain\Accuracy\CartonBox\Entities\CartonBox;
use App\Domain\Accuracy\Validation\Entities\Item;

class RatioValidationStrategy implements ValidationStrategy
{
    public function validate(CartonBox $carton, Item $item): void
    {
        $cartonDetails = $this->getAttributes($carton);
        $itemDetails = $item->getDetails();

        $itemStyle = $itemDetails['Style'] ?? '-';
        $itemSize = $itemDetails['Size'] ?? '-';
        $itemColor = $itemDetails['Color'] ?? '-';
        $itemContract = $itemDetails['Contract'] ?? '-';

        $expectedRatio = collect($cartonDetails)->first(function ($cartonItem) use ($itemStyle, $itemSize, $itemColor, $itemContract) {
            $attributes = $cartonItem['attributes'] ?? [];
            return isset($attributes['Style'], $attributes['Size'], $attributes['Color'], $attributes['Contract']) &&
                $attributes['Style'] === $itemStyle &&
                $attributes['Size'] === $itemSize &&
                $attributes['Color'] === $itemColor &&
                $attributes['Contract'] === $itemContract;
        });

        if (!$expectedRatio) {
            throw new \Exception("Attribute Mismatch! No item matches the carton’s ratio rules (Style: $itemStyle, Size: $itemSize, Color: $itemColor, Contract: $itemContract).");
        }

        $requiredQuantity = (int) ($expectedRatio['attributes']['Quantity_PCS'] ?? 0);
        $validatedQuantity = collect($carton->getItems())->filter(function ($cartonItem) use ($itemStyle, $itemSize, $itemColor, $itemContract) {
            $details = $cartonItem->getDetails();
            return ($details['Style'] ?? '-') === $itemStyle &&
                ($details['Size'] ?? '-') === $itemSize &&
                ($details['Color'] ?? '-') === $itemColor &&
                ($details['Contract'] ?? '-') === $itemContract;
        })->count();

        if ($validatedQuantity >= $requiredQuantity) {
            throw new \Exception("Quantity Exceeded! The quantity for this item (Contract: $itemContract, Size: $itemSize, Color: $itemColor) has reached the maximum allowed of $requiredQuantity.");
        }
    }

    public function getAttributes(CartonBox $carton): array
    {
        $details = $carton->getPackingList()?->getDetails() ?? [];
        return is_string($details) ? json_decode($details, true) ?? [] : $details;
    }
}
