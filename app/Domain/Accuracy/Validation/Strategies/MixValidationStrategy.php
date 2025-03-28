<?php

namespace App\Domain\Accuracy\Validation\Strategies;

use App\Domain\Accuracy\CartonBox\Entities\CartonBox;
use App\Domain\Accuracy\Validation\Entities\Item;
use Illuminate\Support\Facades\Log;

/**
 * Strategi validasi untuk carton box dengan tipe MIX.
 *
 * Kelas ini mengimplementasikan ValidationStrategy untuk memvalidasi item
 * terhadap aturan MIX pada carton box, termasuk pencocokan atribut dan
 * batasan kuantitas.
 */

class MixValidationStrategy implements ValidationStrategy
{
    /**
     * Memvalidasi item terhadap aturan MIX pada carton box.
     *
     * Metode ini memeriksa apakah item sesuai dengan salah satu kombinasi atribut
     * yang diizinkan dalam aturan MIX carton box dan memastikan kuantitas item
     * tidak melebihi batas yang ditentukan.
     *
     * @param CartonBox $carton Carton box yang berisi aturan MIX
     * @param Item $item Item yang akan divalidasi
     *
     * @throws \Exception Ketika atribut item tidak cocok dengan kombinasi yang diizinkan
     * @throws \Exception Ketika kuantitas item melebihi batas yang ditentukan
     *
     * @return void
     */

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

    /**
     * Mengambil atribut MIX dari carton box.
     *
     * Metode ini mengekstrak detail packing list carton box dan memproses
     * data JSON jika diperlukan untuk mendapatkan atribut MIX.
     *
     * @param CartonBox $carton Carton box yang berisi aturan MIX
     *
     * @return array Array yang berisi atribut MIX dari carton box
     */

    public function getAttributes(CartonBox $carton): array
    {
        return $carton->getPackingList()?->getDetails() ?? [];
    }
}
