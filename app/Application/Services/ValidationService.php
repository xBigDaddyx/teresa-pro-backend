<?php

namespace App\Application\Services;

use App\Infrastructure\Repositories\CartonBoxRepository;
use App\Infrastructure\Repositories\ItemRepository;
use App\Domain\Accuracy\CartonBox\Entities\CartonBox;
use App\Domain\Accuracy\CartonBox\Data\CartonBoxData;
use App\Domain\Accuracy\Validation\Entities\Item;
use App\Domain\Accuracy\Validation\Data\ItemData;
use App\Domain\Accuracy\Validation\ValueObjects\AccuracyRule;
use App\Domain\Accuracy\Validation\Strategies\SolidValidationStrategy;
use App\Domain\Accuracy\Validation\Strategies\RatioValidationStrategy;
use App\Domain\Accuracy\Validation\Strategies\MixValidationStrategy;
use App\Events\CartonValidated;

/**
 * Layanan untuk validasi item dalam carton box berdasarkan aturan akurasi yang berbeda.
 */
class ValidationService
{
    /**
     * Repository untuk entitas CartonBox.
     *
     * @var CartonBoxRepository
     */
    private $cartonBoxRepository;

    /**
     * Repository untuk entitas Item.
     *
     * @var ItemRepository
     */
    private $itemRepository;

    /**
     * Strategi validasi yang tersedia berdasarkan aturan akurasi.
     *
     * @var array
     */
    private $strategies;

    /**
     * Membuat instance baru dari ValidationService.
     *
     * @param CartonBoxRepository $cartonBoxRepository Repository untuk carton box
     * @param ItemRepository $itemRepository Repository untuk item
     */
    public function __construct(CartonBoxRepository $cartonBoxRepository, ItemRepository $itemRepository)
    {
        $this->cartonBoxRepository = $cartonBoxRepository;
        $this->itemRepository = $itemRepository;
        $this->strategies = [
            AccuracyRule::SOLID => new SolidValidationStrategy(),
            AccuracyRule::RATIO => new RatioValidationStrategy(),
            AccuracyRule::MIX => new MixValidationStrategy(),
        ];
    }

    /**
     * Memvalidasi item dalam carton box.
     *
     * @param mixed $cartonBoxId ID dari carton box yang akan divalidasi
     * @param string $barcode Barcode dari item yang akan divalidasi
     * @param mixed $validatedBy ID atau nama pengguna yang melakukan validasi
     *
     * @return array Array berisi data carton box dan item yang divalidasi
     *
     * @throws \Exception Jika carton box tidak ditemukan
     * @throws \Exception Jika carton sudah divalidasi penuh
     * @throws \Exception Jika item tidak ditemukan dengan barcode tertentu
     * @throws \Exception Jika tidak ada item yang cocok dengan atribut carton
     * @throws \Exception Jika item number tidak cocok dengan rekaman item
     */
    public function validateCartonItem($cartonBoxId, $barcode, $validatedBy): array
    {
        $cartonBox = $this->cartonBoxRepository->find($cartonBoxId);
        if (!$cartonBox) {
            throw new \Exception('Carton box tidak ditemukan');
        }

        if ($cartonBox->getValidationStatus() === 'VALIDATED') {
            throw new \Exception('Carton sudah divalidasi penuh');
        }

        $parsedBarcode = $this->parseBarcode($barcode);
        $lpn = $parsedBarcode['lpn'] ?? $barcode;
        $itemNumber = $parsedBarcode['item_number'] ?? null;

        $items = $this->itemRepository->findByBarcode($lpn);
        if (empty($items)) {
            throw new \Exception('Item tidak ditemukan dengan barcode: ' . $lpn);
        }

        $cartonAttributes = $this->getCartonAttributes($cartonBox);
        $item = $this->determineItem($items, $cartonAttributes, $cartonBox);

        if (!$item) {
            throw new \Exception('Tidak ada item yang cocok dengan atribut carton');
        }

        if (!$this->validateItemNumber($item, $itemNumber)) {
            throw new \Exception('Item number tidak cocok dengan rekaman item');
        }

        $rule = $cartonBox->getPackingList()?->getDetails()['carton_validation_rule'] ?? AccuracyRule::SOLID;
        $strategy = $this->strategies[$rule] ?? $this->strategies[AccuracyRule::SOLID];
        $strategy->validate($cartonBox, $item);

        $this->attachItemToCarton($cartonBox, $item, $validatedBy);

        return [
            'carton_box' => $this->toCartonData($cartonBox)->toArray(),
            'item' => (new ItemData($item->getId(), $item->getBarcode(), $item->getDetails()))->toArray(),
        ];
    }

    /**
     * Parsing barcode untuk mendapatkan LPN dan nomor item.
     *
     * @param string|null $barcode Barcode yang akan diparse
     *
     * @return array Array berisi 'lpn' dan 'item_number'
     */
    private function parseBarcode($barcode): array
    {
        if (!$barcode || !str_contains($barcode, '&')) {
            return ['lpn' => $barcode, 'item_number' => null];
        }

        $parts = explode('&', trim($barcode, '&'));
        $result = ['lpn' => null, 'item_number' => null];

        foreach ($parts as $part) {
            if (!str_contains($part, '=')) {
                $result['lpn'] = $part;
            } elseif (str_starts_with($part, 'item_number=')) {
                [, $value] = explode('=', $part, 2);
                $result['item_number'] = $value;
            } elseif (str_starts_with(strtolower($part), 'lpn=')) {
                [, $value] = explode('=', $part, 2);
                $result['lpn'] = $value;
            }
        }

        return $result;
    }

    /**
     * Mendapatkan atribut carton box sesuai dengan strategi validasi yang berlaku.
     *
     * @param CartonBox $cartonBox Entitas carton box
     *
     * @return array Array berisi atribut carton box
     */
    private function getCartonAttributes(CartonBox $cartonBox): array
    {
        $rule = $cartonBox->getPackingList()?->getDetails()['carton_validation_rule'] ?? AccuracyRule::SOLID;
        $strategy = $this->strategies[$rule] ?? $this->strategies[AccuracyRule::SOLID];
        return $strategy->getAttributes($cartonBox);
    }

    /**
     * Menentukan item yang cocok dengan atribut carton berdasarkan strategi validasi.
     *
     * @param array $items Daftar item yang akan diperiksa
     * @param array $cartonAttributes Atribut carton untuk pencocokan
     * @param CartonBox $cartonBox Entitas carton box
     *
     * @return Item|null Item yang cocok atau null jika tidak ditemukan
     */
    private function determineItem(array $items, array $cartonAttributes, CartonBox $cartonBox): ?Item
    {
        $rule = $cartonBox->getPackingList()?->getDetails()['carton_validation_rule'] ?? AccuracyRule::SOLID;

        if (in_array($rule, [AccuracyRule::RATIO, AccuracyRule::MIX])) {
            foreach ($items as $item) {
                $itemDetails = $item->getDetails();
                $itemContract = $itemDetails['Contract'] ?? '-';
                $itemStyle = $itemDetails['Style'] ?? '-';
                $itemSize = $itemDetails['Size'] ?? '-';
                $itemColor = $itemDetails['Color'] ?? '-';

                $matchingAttributes = collect($cartonAttributes)->first(function ($attributes) use ($itemContract, $itemStyle, $itemSize, $itemColor) {
                    $cartonAttrs = $attributes['attributes'] ?? [];
                    return strcasecmp($cartonAttrs['Contract'] ?? '', $itemContract) === 0 &&
                        strcasecmp($cartonAttrs['Style'] ?? '', $itemStyle) === 0 &&
                        strcasecmp($cartonAttrs['Size'] ?? '', $itemSize) === 0 &&
                        strcasecmp($cartonAttrs['Color'] ?? '', $itemColor) === 0;
                });

                if ($matchingAttributes) {
                    return $item;
                }
            }
            return null;
        }

        return $items[0] ?? null;
    }

    /**
     * Validasi nomor item jika disediakan dalam barcode.
     *
     * @param Item $item Entitas item
     * @param string|null $itemNumber Nomor item yang akan divalidasi
     *
     * @return bool True jika valid atau tidak perlu validasi, False jika tidak valid
     */
    private function validateItemNumber(Item $item, ?string $itemNumber): bool
    {
        if ($itemNumber && ($item->getDetails()['item_number'] ?? null) && $item->getDetails()['item_number'] !== $itemNumber) {
            return false;
        }
        return true;
    }

    /**
     * Melampirkan item yang tervalidasi ke carton box dan memeriksa status validasi.
     *
     * @param CartonBox $cartonBox Carton box yang akan diupdate
     * @param Item $item Item yang akan dilampirkan
     * @param mixed $validatedBy ID atau nama pengguna yang melakukan validasi
     *
     * @return void
     */
    private function attachItemToCarton(CartonBox $cartonBox, Item $item, $validatedBy): void
    {
        $cartonBox->addItem($item);
        $this->itemRepository->save($item, $cartonBox->getId(), $validatedBy);

        if (count($cartonBox->getItems()) >= $cartonBox->getItemsQuantity()) {
            $cartonBox->validate();
            $this->cartonBoxRepository->save($cartonBox);

            // Picu event saat validasi selesai
            event(new CartonValidated($cartonBox));
        }

        $this->cartonBoxRepository->save($cartonBox);
    }

    /**
     * Mengubah entitas CartonBox menjadi objek data transfer.
     *
     * @param CartonBox $cartonBox Entitas carton box
     *
     * @return CartonBoxData Data transfer object untuk carton box
     */
    private function toCartonData(CartonBox $cartonBox): CartonBoxData
    {
        $buyer = $cartonBox->getPackingList()?->getBuyer();
        return new CartonBoxData(
            $cartonBox->getId(),
            $cartonBox->getBarcode(),
            $cartonBox->getInternalSku(),
            $cartonBox->getValidationStatus(),
            $cartonBox->getStatus()->getValue(),
            $cartonBox->getProcessedAt(),
            $cartonBox->getProcessedBy(),
            $cartonBox->getItemsQuantity(),
            $buyer ? [
                'id' => $buyer->getId(),
                'name' => $buyer->getName(),
                'email' => $buyer->getEmail(),
            ] : null
        );
    }

    /**
     * Mencari item berdasarkan barcode (LPN).
     *
     * @param string $lpn Barcode (LPN) yang akan dicari
     *
     * @return array Array berisi entitas Item yang ditemukan
     */
    public function findByBarcode(string $lpn): array
    {
        $items = \App\Infrastructure\Models\ItemModel::where('barcode', $lpn)->get();
        return $items->map(function ($model) {
            return new Item(
                $model->id,
                $model->barcode,
                $model->internal_sku,
                $model->name,
                $model->details,
                $model->has_polybag
            );
        })->all();
    }
}
