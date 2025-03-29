<?php

namespace App\Infrastructure\Repositories;

use App\Domain\Accuracy\Shared\Repositories\UserRepository;
use App\Models\User;

class EloquentUserRepository implements UserRepository
{
    public function findByEmail(string $email)
    {
        return User::where('email', $email)->first();
    }

    public function create(array $data)
    {
        return User::create($data);
    }
}
