<?php

namespace App\Domain\Accuracy\PackingList\Entities;

use App\Domain\Accuracy\Shared\Entities\Buyer;

class PackingList
{

    public function __construct(
        private string $id,
        private string $purchaseOrderNumber,
        private int $cartonBoxesQuantity,
        private ?Buyer $buyer = null,
        private array $details = []
    ) {}

    public function getId(): string { return $this->id; }
    public function getPurchaseOrderNumber(): string { return $this->purchaseOrderNumber; }
    public function getCartonBoxesQuantity(): int { return $this->cartonBoxesQuantity; }
    public function getBuyer(): ?Buyer { return $this->buyer; }
    public function getDetails(): array { return $this->details; }
}
