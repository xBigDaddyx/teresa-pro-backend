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
class ValidationService
{
    private $cartonBoxRepository;
    private $itemRepository;
    private $strategies;

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

    private function getCartonAttributes(CartonBox $cartonBox): array
    {
        $rule = $cartonBox->getPackingList()?->getDetails()['carton_validation_rule'] ?? AccuracyRule::SOLID;
        $strategy = $this->strategies[$rule] ?? $this->strategies[AccuracyRule::SOLID];
        return $strategy->getAttributes($cartonBox);
    }

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

    private function validateItemNumber(Item $item, ?string $itemNumber): bool
    {
        if ($itemNumber && ($item->getDetails()['item_number'] ?? null) && $item->getDetails()['item_number'] !== $itemNumber) {
            return false;
        }
        return true;
    }

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
