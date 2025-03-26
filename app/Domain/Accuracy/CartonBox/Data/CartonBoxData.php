<?php

namespace App\Domain\Accuracy\CartonBox\Data;

class CartonBoxData
{
    public function __construct(
        public readonly string $id,
        public readonly string $barcode,
        public readonly string $internal_sku,
        public readonly string $validation_status,
        public readonly string $status,
        public readonly ?string $processed_at,
        public readonly ?int $processed_by,
        public readonly int $items_quantity,
        public readonly ?array $buyer,
        public readonly ?string $next_step = null // Tambahkan ini
    ) {}

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'barcode' => $this->barcode,
            'internal_sku' => $this->internal_sku,
            'validation_status' => $this->validation_status,
            'status' => $this->status,
            'processed_at' => $this->processed_at,
            'processed_by' => $this->processed_by,
            'items_quantity' => $this->items_quantity,
            'buyer' => $this->buyer,
            'next_step' => $this->next_step,
        ];
    }
}
