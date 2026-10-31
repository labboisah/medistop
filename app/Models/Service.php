<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Service extends Model
{
    protected $casts = [
        'is_xray' => 'boolean',
    ];

    protected $fillable = [
        'category_id',
        'name',
        'price',
        'is_xray',
    ];
    public function category(){
        return $this->belongsTo(Category::class);
    }
}
