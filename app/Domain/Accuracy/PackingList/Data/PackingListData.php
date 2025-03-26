<?php

namespace App\Domain\Accuracy\PackingList\Data;

class PackingListData
{
    public function __construct(
        public readonly string $id,
        public readonly string $purchaseOrderNumber,
        public readonly int $cartonBoxesQuantity,
        public readonly ?array $buyer,
        public readonly array $details = []
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            id: $data['id'] ?? '',
            purchaseOrderNumber: $data['purchase_order_number'] ?? '',
            cartonBoxesQuantity: $data['carton_boxes_quantity'] ?? 0,
            buyer: $data['buyer'] ?? null,
            details: $data['details'] ?? []
        );
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'purchase_order_number' => $this->purchaseOrderNumber,
            'carton_boxes_quantity' => $this->cartonBoxesQuantity,
            'buyer' => $this->buyer,
            'details' => $this->details,
        ];
    }
}
