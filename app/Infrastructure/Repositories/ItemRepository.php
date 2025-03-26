<?php

namespace App\Infrastructure\Repositories;

use App\Infrastructure\Models\ItemModel;
use App\Domain\Accuracy\Validation\Entities\Item;

class ItemRepository
{
    public function findByBarcode($barcode): array
    {
        $models = ItemModel::where('barcode', $barcode)->get();
        return $models->map(fn($model) => $this->toEntity($model))->all();
    }

    public function save(Item $item, $cartonBoxId, $validatedBy): void
    {
        $model = ItemModel::find($item->getId()) ?? new ItemModel();
        $model->fill([
            'id' => $item->getId(),
            'barcode' => $item->getBarcode(),
            'internal_sku' => $item->getInternalSku(),
            'name' => $item->getName(),
            'details' => $item->getDetails(),
            'has_polybag' => $item->hasPolybag(),
        ])->save();

        $model->cartonBoxes()->syncWithoutDetaching([
            $cartonBoxId => [
                'is_validated' => true,
                'validated_at' => now(),
                'validated_by' => $validatedBy,
            ]
        ]);
    }

    private function toEntity(ItemModel $model): Item
    {
        return new Item(
            $model->id,
            $model->barcode,
            $model->internal_sku,
            $model->name,
            $model->details,
            $model->has_polybag
        );
    }
}
