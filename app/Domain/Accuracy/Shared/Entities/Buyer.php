<?php

namespace App\Domain\Accuracy\Shared\Entities;

class Buyer
{
    private $id;
    private $name;
    private $email;
    private $phone;
    private $address;
    private $rules;

    public function __construct($id, $name, $email, $phone, $address, array $rules = [])
    {
        $this->id = $id;
        $this->name = $name;
        $this->email = $email;
        $this->phone = $phone;
        $this->address = $address;
        $this->rules = $rules;
    }

    public function getId() { return $this->id; }
    public function getName() { return $this->name; }
    public function getEmail() { return $this->email; }
    public function getPhone() { return $this->phone; }
    public function getAddress() { return $this->address; }
    public function getRules() { return $this->rules; }
}
