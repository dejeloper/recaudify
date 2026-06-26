<?php

namespace App\Models;

use App\Models\Concerns\LogsModelActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Rate extends Model
{
    use LogsModelActivity, SoftDeletes;

    protected $fillable = ["name", "product_id", "value", "installments", "installment_value", "discount", "active"];

    protected $attributes = [
        "discount" => 0,
        "active" => true,
    ];

    protected $casts = [
        "value" => "integer",
        "installments" => "integer",
        "installment_value" => "integer",
        "discount" => "integer",
        "active" => "boolean",
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    protected function logName(): string
    {
        return "catalogos";
    }

    protected function activitylogFields(): array
    {
        return ["name", "product_id", "value", "installments", "installment_value", "discount", "active"];
    }
}
