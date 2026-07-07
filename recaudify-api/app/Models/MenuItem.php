<?php

namespace App\Models;

use App\Models\Concerns\LogsModelActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class MenuItem extends Model
{
    use LogsModelActivity, SoftDeletes;

    protected $fillable = ["parent_id", "label", "icons", "route", "permission", "order", "is_active"];

    protected function casts(): array
    {
        return [
            "icons" => "array",
            "order" => "integer",
            "is_active" => "boolean",
        ];
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(MenuItem::class, "parent_id");
    }

    public function children(): HasMany
    {
        return $this->hasMany(MenuItem::class, "parent_id")->orderBy("order");
    }

    public function depth(): int
    {
        $depth = 0;
        $node = $this;

        while ($node->parent_id !== null) {
            $node = $node->parent()->first();
            $depth++;
        }

        return $depth;
    }

    protected function logName(): string
    {
        return "configuracion";
    }

    protected function activitylogFields(): array
    {
        return ["label", "route", "permission", "order", "is_active", "parent_id"];
    }
}
