<?php

namespace App\Domain\Accuracy\Shared\Repositories;

interface UserRepository
{
    public function findByEmail(string $email);
    public function create(array $data);
}
