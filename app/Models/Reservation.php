<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;

class Reservation extends Model
{
    protected $fillable = ['user_id', 'space_id', 'start_time', 'end_time', 'status', 'notes'];

    protected $casts = [
        'start_time' => 'datetime',
        'end_time' => 'datetime',
    ];

    protected $appends = ['duration_hours', 'status_label'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function space()
    {
        return $this->belongsTo(Space::class);
    }

    public function scopeOverlapping(Builder $query, $start, $end): Builder
    {
        return $query->whereIn('status', ['pending', 'confirmed'])
            ->where(function ($q) use ($start, $end) {
                $q->where('start_time', '<', $end)
                  ->where('end_time', '>', $start);
            });
    }

    protected function durationHours(): Attribute
    {
        return Attribute::make(
            get: fn () => round($this->start_time->diffInMinutes($this->end_time) / 60, 2)
        );
    }

    protected function statusLabel(): Attribute
    {
        $labels = [
            'pending'   => 'Pendiente',
            'confirmed' => 'Confirmada',
            'cancelled' => 'Cancelada',
            'completed' => 'Completada',
        ];

        return Attribute::make(
            get: fn () => $labels[$this->status] ?? $this->status
        );
    }
}
