<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;

    public function ingredients()
    {
        return $this->belongsToMany(Ingredient::class, 'product_ingredient', 'product_id', 'ingredient_id')->withPivot('quantity');
    }
}
