<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Contact extends Model
{
    protected $table = 'contacts';

    protected $fillable = [
        'name',
        'email',
        'type',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    // Mapa anglických typů na české labely pro UI
    public const TYPE_LABELS = [
        'supplier' => 'Dodavatel',
        'customer' => 'Zákazník',
        'user'     => 'Uživatel',
    ];

    public function getTypeLabelAttribute(): string
    {
        return self::TYPE_LABELS[$this->type] ?? $this->type;
    }

    public function toArray()
    {
        $arr = parent::toArray();
        $arr['type_label'] = $this->type_label;
        return $arr;
    }
}