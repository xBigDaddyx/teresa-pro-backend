<?php

namespace App\Domain\Accuracy\Validation\Strategies;

use App\Domain\Accuracy\CartonBox\Entities\CartonBox;
use App\Domain\Accuracy\Validation\Entities\Item;

/**
 * Strategi validasi untuk carton box dengan tipe RATIO.
 *
 * Kelas ini mengimplementasikan ValidationStrategy untuk memvalidasi item
 * terhadap aturan RATIO pada carton box, memastikan setiap kombinasi atribut
 * memenuhi jumlah yang ditentukan dalam rasio.
 */

class RatioValidationStrategy implements ValidationStrategy
{
    /**
     * Memvalidasi item terhadap aturan RATIO pada carton box.
     *
     * Metode ini memeriksa apakah item sesuai dengan salah satu kombinasi atribut
     * yang didefinisikan dalam aturan RATIO carton box dan memastikan kuantitas item
     * tidak melebihi rasio yang ditentukan.
     *
     * @param CartonBox $carton Carton box yang berisi aturan RATIO
     * @param Item $item Item yang akan divalidasi
     *
     * @throws \Exception Ketika atribut item tidak cocok dengan kombinasi yang didefinisikan dalam RATIO
     * @throws \Exception Ketika kuantitas item melebihi jumlah yang ditentukan dalam rasio
     *
     * @return void
     */

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

    /**
     * Mengambil atribut RATIO dari carton box.
     *
     * Metode ini mengekstrak detail packing list carton box dan memproses
     * data JSON jika diperlukan untuk mendapatkan aturan RATIO.
     *
     * @param CartonBox $carton Carton box yang berisi aturan RATIO
     *
     * @return array Array yang berisi aturan RATIO dari carton box
     */

    public function getAttributes(CartonBox $carton): array
    {
        return $carton->getPackingList()?->getDetails() ?? [];
    }
}
