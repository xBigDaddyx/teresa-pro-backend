<?php

namespace App\Domain\Accuracy\Validation\Strategies;

use App\Domain\Accuracy\CartonBox\Entities\CartonBox;
use App\Domain\Accuracy\Validation\Entities\Item;

class MixValidationStrategy implements ValidationStrategy
{
    public function validate(CartonBox $carton, Item $item): void
    {
        $cartonAttributes = $this->getAttributes($carton);
        $itemDetails = $item->getDetails();

        $itemStyle = $itemDetails['Style'] ?? null;
        $itemSize = $itemDetails['Size'] ?? null;
        $itemColor = $itemDetails['Color'] ?? null;
        $itemContract = $itemDetails['Contract'] ?? null;

        $isValid = collect($cartonAttributes)->contains(function ($cartonItem) use ($itemStyle, $itemSize, $itemColor, $itemContract) {
            $attributes = $cartonItem['attributes'] ?? [];
            return isset($attributes['Style'], $attributes['Size'], $attributes['Color'], $attributes['Contract']) &&
                $attributes['Style'] === $itemStyle &&
                $attributes['Size'] === $itemSize &&
                $attributes['Color'] === $itemColor &&
                $attributes['Contract'] === $itemContract;
        });

        if (!$isValid) {
            throw new \Exception('Attribute Mismatch! The item attributes do not match any allowed combination in the carton’s MIX rules.');
        }

        $validatedQuantity = collect($carton->getItems())->filter(function ($cartonItem) use ($itemStyle, $itemSize, $itemColor, $itemContract) {
            $details = $cartonItem->getDetails();
            return ($details['Style'] ?? null) === $itemStyle &&
                ($details['Size'] ?? null) === $itemSize &&
                ($details['Color'] ?? null) === $itemColor &&
                ($details['Contract'] ?? null) === $itemContract;
        })->count();

        $expectedQuantity = collect($cartonAttributes)->first(function ($cartonItem) use ($itemStyle, $itemSize, $itemColor, $itemContract) {
            $attributes = $cartonItem['attributes'] ?? [];
            return $attributes['Style'] === $itemStyle &&
                $attributes['Size'] === $itemSize &&
                $attributes['Color'] === $itemColor &&
                $attributes['Contract'] === $itemContract;
        })['attributes']['Quantity_PCS'] ?? PHP_INT_MAX;

        if ($validatedQuantity >= $expectedQuantity) {
            throw new \Exception("Quantity Exceeded! The item quantity has reached the allowed limit for this MIX combination.");
        }
    }

    public function getAttributes(CartonBox $carton): array
    {
        $details = $carton->getPackingList()?->getDetails() ?? [];
        return is_string($details) ? json_decode($details, true) ?? [] : $details;
    }
}
