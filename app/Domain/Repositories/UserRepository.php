<?php

namespace App\Domain\Repositories;

interface UserRepository
{
    public function findByEmail(string $email);
    public function create(array $data);
}
