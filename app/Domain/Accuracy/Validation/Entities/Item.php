<?php

namespace App\Domain\Accuracy\Validation\Entities;

class Item
{
    private $id;
    private $barcode;
    private $internalSku;
    private $name;
    private $details;
    private $hasPolybag;

    public function __construct($id, $barcode, $internalSku, $name, array $details, $hasPolybag = false)
    {
        $this->id = $id;
        $this->barcode = $barcode;
        $this->internalSku = $internalSku;
        $this->name = $name;
        $this->details = $details;
        $this->hasPolybag = $hasPolybag;
    }

    public function getId() { return $this->id; }
    public function getBarcode() { return $this->barcode; }
    public function getInternalSku() { return $this->internalSku; }
    public function getName() { return $this->name; }
    public function getDetails() { return $this->details; }
    public function hasPolybag() { return $this->hasPolybag; }
}
