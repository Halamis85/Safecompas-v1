<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
class Produkt extends Model
{
    protected $table = 'produkty';

    protected $fillable = [
        'nazev',
        'obrazek',
        'dostupne_velikosti',
        'druh_id',
        'cena'
    ];
    protected $cena = [
        'cena' => 'decimal:2'
    ];
    public function druh(): BelongsTo
    {
        return $this->belongsTo(DruhOopp::class, 'druh_id');
    }

    public function objednavky(): HasMany
    {
        return $this->hasMany(Objednavka::class);
    }

    public function getDostupneVelikostiAttribute($value)
    {
        return $value ? explode(',', $value) : [];
    }
}
