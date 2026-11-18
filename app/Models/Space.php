<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class Space extends Model
{
    protected $fillable = ['space_type_id', 'name', 'floor', 'capacity', 'price_per_hour', 'is_active'];

    protected $casts = [
        'is_active' => 'boolean',
        'price_per_hour' => 'decimal:2',
    ];

    public function spaceType()
    {
        return $this->belongsTo(SpaceType::class);
    }

    public function reservations()
    {
        return $this->hasMany(Reservation::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }
}