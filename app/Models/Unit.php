<?php

namespace App\Models;

use Database\Factories\UnitFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property string $nama_unit
 * @property int|null $parent_unit_id
 * @property int|null $kepala_unit_id
 */
class Unit extends Model
{
    /** @use HasFactory<UnitFactory> */
    use HasFactory;

    protected $fillable = [
        'nama_unit',
        'parent_unit_id',
        'kepala_unit_id',
    ];

    /** @return BelongsTo<Unit, $this> */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(Unit::class, 'parent_unit_id');
    }

    /** @return HasMany<Unit, $this> */
    public function children(): HasMany
    {
        return $this->hasMany(Unit::class, 'parent_unit_id');
    }

    /** @return BelongsTo<Employee, $this> */
    public function kepalaUnit(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'kepala_unit_id');
    }

    /** @return HasMany<Employee, $this> */
    public function employees(): HasMany
    {
        return $this->hasMany(Employee::class, 'unit_id');
    }
}
