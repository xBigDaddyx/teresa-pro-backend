<?php

namespace App\Domain\Accuracy\CartonBox\Entities;

use App\Domain\Accuracy\CartonBox\ValueObjects\Status;
use App\Domain\Accuracy\PackingList\Entities\PackingList;
use App\Domain\Accuracy\Validation\Entities\Item;

class CartonBox
{
    private $id;
    private $barcode;
    private $internalSku;
    private $validationStatus;
    private $status;
    private $processedAt;
    private $processedBy;
    private $itemsQuantity;
    private $packingList;
    private $items = [];

    public function __construct(
        $id,
        $barcode,
        $internalSku,
        Status $status,
        $itemsQuantity,
        ?PackingList $packingList = null
    ) {
        $this->id = $id;
        $this->barcode = $barcode;
        $this->internalSku = $internalSku;
        $this->validationStatus = 'PENDING';
        $this->status = $status;
        $this->itemsQuantity = $itemsQuantity;
        $this->packingList = $packingList;
    }

    public function getId() { return $this->id; }
    public function getBarcode() { return $this->barcode; }
    public function getInternalSku() { return $this->internalSku; }
    public function getValidationStatus() { return $this->validationStatus; }
    public function getStatus() { return $this->status; }
    public function getProcessedAt() { return $this->processedAt; }
    public function getProcessedBy() { return $this->processedBy; }
    public function getItemsQuantity() { return $this->itemsQuantity; }
    public function getItems() { return $this->items; }
    public function getPackingList(): ?PackingList { return $this->packingList; }

    public function process($processedBy): void
    {
        $this->validationStatus = 'PROCESS';
        $this->status = new Status(Status::OPEN);
        $this->processedBy = $processedBy;
        $this->processedAt = now()->toDateTimeString();
    }

    public function addItem(Item $item): void
    {
        $this->items[] = $item;
        $this->validate();
        $this->updateStatus();
    }

    public function validate(): void
    {
        if ($this->validationStatus === 'PROCESS' && count($this->items) > 0) {
            $this->validationStatus = 'VALIDATED';
        }
    }

    private function updateStatus(): void
    {
        if (count($this->items) >= $this->itemsQuantity && $this->validationStatus === 'VALIDATED') {
            $this->status = new Status(Status::SEALED);
        }
    }
}
