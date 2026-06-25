<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Rate extends Model
{
    use SoftDeletes;

    protected $fillable = ['name', 'product_id', 'value', 'installments', 'installment_value', 'discount', 'active'];

    protected $casts = [
        'value' => 'integer',
        'installments' => 'integer',
        'installment_value' => 'integer',
        'discount' => 'integer',
        'active' => 'boolean',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
