<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\TransactionItem;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'code',
        'price',
        'stock',
        'is_active',
    ];

    public function transactionItems()
    {
        return $this->hasMany(TransactionItem::class);
    }
}

