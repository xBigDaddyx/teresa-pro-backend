<?php

namespace App\Infrastructure\Repositories;

use App\Infrastructure\Models\CartonBoxModel;
use App\Domain\Accuracy\CartonBox\Entities\CartonBox;
use App\Domain\Accuracy\CartonBox\ValueObjects\Status;
use App\Domain\Accuracy\PackingList\Entities\PackingList;
use App\Domain\Accuracy\Shared\Entities\Buyer;

class CartonBoxRepository
{
    public function find($id): ?CartonBox
    {
        $model = CartonBoxModel::with('packingList.buyer')->find($id);
        return $model ? $this->toEntity($model) : null;
    }

    public function findByFilters($barcode = null, $po = null, $sku = null): array
    {
        $query = CartonBoxModel::query()
            ->leftJoin('packing_lists', 'carton_boxes.packing_list_id', '=', 'packing_lists.id')
            ->select('carton_boxes.*', 'packing_lists.purchase_order_number', 'packing_lists.buyer_id')
            ->where(function ($query) {
                $query->where('carton_boxes.validation_status', '!=', 'VALIDATED')
                    ->orWhereNull('carton_boxes.validation_status');
            });

        if ($barcode) $query->where('carton_boxes.barcode', $barcode);
        if ($po) $query->where('packing_lists.purchase_order_number', $po);
        if ($sku) $query->where('carton_boxes.internal_sku', $sku);

        return $query->get()->map(fn($model) => $this->toEntity($model))->all();
    }

    public function save(CartonBox $entity): void
    {
        $model = CartonBoxModel::find($entity->getId()) ?? new CartonBoxModel();
        $model->fill([
            'id' => $entity->getId(),
            'barcode' => $entity->getBarcode(),
            'internal_sku' => $entity->getInternalSku(),
            'validation_status' => $entity->getValidationStatus(),
            'status' => $entity->getStatus()->getValue(),
            'processed_at' => $entity->getProcessedAt(),
            'processed_by' => $entity->getProcessedBy(),
            'items_quantity' => $entity->getItemsQuantity(),
            'packing_list_id' => $entity->getPackingList()?->getId(),
        ])->save();
    }

    private function toEntity(CartonBoxModel $model): CartonBox
    {
        $packingList = $model->packingList ? new PackingList(
            $model->packingList->id,
            $model->packingList->purchase_order_number,
            $model->packingList->carton_boxes_quantity,
            null,
            json_decode($model->packingList->details ?? '[]', true)
        ) : null;

        // Pastikan status adalah string
        $statusValue = $model->status instanceof \App\Enums\CartonStatus
            ? $model->status->value
            : (string) $model->status;

        $carton = new CartonBox(
            $model->id,
            $model->barcode,
            $model->internal_sku,
            new Status($statusValue),
            $model->items_quantity,
            $packingList
        );

        if ($model->processed_at) {
            $carton->process($model->processed_by);
        }

        return $carton;
    }
}
