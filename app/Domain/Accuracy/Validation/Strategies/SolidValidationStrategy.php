<?php

namespace App\Domain\Accuracy\Validation\Strategies;

use App\Domain\Accuracy\CartonBox\Entities\CartonBox;
use App\Domain\Accuracy\Validation\Entities\Item;

/**
 * Strategi validasi untuk carton box dengan tipe SOLID.
 *
 * Kelas ini mengimplementasikan ValidationStrategy untuk memvalidasi item
 * terhadap aturan SOLID pada carton box, yang mengharuskan semua item
 * dalam box memiliki atribut Size dan Color yang sama.
 */

class SolidValidationStrategy implements ValidationStrategy
{
    /**
     * Memvalidasi item terhadap aturan SOLID pada carton box.
     *
     * Metode ini memeriksa apakah ukuran dan warna item sesuai dengan
     * ukuran dan warna yang ditentukan untuk carton box tipe SOLID.
     * Perbandingan dilakukan tanpa memperhatikan case sensitivity.
     *
     * @param CartonBox $carton Carton box yang berisi aturan SOLID
     * @param Item $item Item yang akan divalidasi
     *
     * @throws \Exception Ketika atribut item tidak cocok dengan atribut carton box
     *
     * @return void
     */

    public function validate(CartonBox $carton, Item $item): void
    {
        $cartonAttributes = $this->getAttributes($carton);
        $itemDetails = $item->getDetails();

        $cartonStyle = $cartonAttributes['Style'] ?? '';
        $itemStyle = $itemDetails['Style'] ?? '';
        $cartonSize = $cartonAttributes['Size'] ?? '';
        $itemSize = $itemDetails['Size'] ?? '';
        $cartonColor = $cartonAttributes['Color'] ?? '';
        $itemColor = $itemDetails['Color'] ?? '';
        $cartonContract = $cartonAttributes['Contract'] ?? '';
        $itemContract = $itemDetails['Contract'] ?? '';

        if (strcasecmp($cartonStyle, $itemStyle) !== 0 ||
            strcasecmp($cartonSize, $itemSize) !== 0 ||
            strcasecmp($cartonColor, $itemColor) !== 0 ||
            strcasecmp($cartonContract, $itemContract) !== 0) {
            throw new \Exception('Attribute Mismatch! The item attributes do not match the carton attributes.');
        }
    }

    /**
     * Mengambil atribut SOLID dari carton box.
     *
     * Metode ini mengekstrak detail packing list carton box dan mengambil
     * atribut dari elemen pertama, karena carton box SOLID hanya memiliki
     * satu set atribut yang berlaku.
     *
     * @param CartonBox $carton Carton box yang berisi aturan SOLID
     *
     * @return array Array yang berisi atribut SOLID dari carton box
     */

    public function getAttributes(CartonBox $carton): array
    {
        $details = $carton->getPackingList()?->getDetails() ?? [];
        return $details['carton_attributes'] ?? [];
    }
}
