<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'description',
        'is_active',
        'colors',
        'image',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'colors' => 'array',
    ];

    public function hoods()
    {
        return $this->hasMany(Hood::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Accessor luôn trả về mảng màu sắc sạch
     */
    public function getColorsAttribute($value): array
    {
        if (is_null($value)) {
            return [];
        }

        if (is_array($value)) {
            return array_values(array_filter(array_map('trim', $value)));
        }

        $decoded = json_decode($value, true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
            return array_values(array_filter(array_map('trim', $decoded)));
        }

        return array_values(array_filter(array_map('trim', explode(',', $value))));
    }

    public function getStatusKeyAttribute(): string
    {
        return $this->is_active ? 'active' : 'inactive';
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status_key) {
            'active' => 'Đang hoạt động',
            'inactive' => 'Không hoạt động',
            default => 'Không hoạt động',
        };
    }

    public function getStatusColorClassAttribute(): string
    {
        return match ($this->status_key) {
            'active' => 'success',
            'inactive' => 'secondary',
            default => 'secondary',
        };
    }
}