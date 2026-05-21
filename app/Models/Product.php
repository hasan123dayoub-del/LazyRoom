<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    protected $fillable = [
        'name',
        'description',
        'price',
        'stock',
        'category_id',
        'supplier_id',
    ];
    public function supplier()
    {
        return $this->belongsTo(User::class, 'supplier_id');
    }
    public function category()
    {
        return $this->belongsTo(Category::class);
    }
}
