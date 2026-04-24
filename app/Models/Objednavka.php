<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo; // ✅ SPRÁVNÝ import


class Objednavka extends Model
{
    protected $table = 'objednavky';

    protected $fillable = [
        'zamestnanec_id',
        'produkt_id',
        'velikost',
        'datum_objednani',
        'datum_vydani',
        'status',
        'podpis_path'
    ];

    protected $casts = [
        'datum_objednani' => 'date',
        'datum_vydani' => 'datetime'
    ];

    public function zamestnanec(): BelongsTo
    {
        return $this->belongsTo(Zamestnanec::class);
    }

    public function produkt(): BelongsTo
    {
        return $this->belongsTo(Produkt::class);
    }
}
