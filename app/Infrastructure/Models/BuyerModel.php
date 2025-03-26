<?php

namespace App\Infrastructure\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class BuyerModel extends Model
{
    use HasUuids;

    protected $table = 'buyers';

    protected $fillable = [
        'name',
        'email',
        'phone',
        'address',
        'city',
        'state',
        'postal_code',
        'country',
        'preferred_language',
        'is_verified',
        'rules',
    ];

    protected $casts = [
        'rules' => 'array',
        'is_verified' => 'boolean',
    ];

    public function packingLists()
    {
        return $this->hasMany(PackingListModel::class, 'buyer_id');
    }
}
