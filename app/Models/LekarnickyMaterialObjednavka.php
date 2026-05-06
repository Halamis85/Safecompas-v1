<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LekarnickyMaterialObjednavka extends Model
{
    use HasFactory;

    protected $table = 'lekarnicky_material_objednavky';

    protected $fillable = [
        'lekarnicky_id',
        'material_id',
        'objednal_user_id',
        'nazev_materialu',
        'typ_materialu',
        'jednotka',
        'mnozstvi',
        'duvod',
        'status',
        'datum_objednani',
        'datum_objednano',
        'datum_vydano',
        'poznamky',
    ];

    protected $casts = [
        'datum_objednani' => 'datetime',
        'datum_objednano' => 'datetime',
        'datum_vydano' => 'datetime',
    ];

    public function lekarnicky()
    {
        return $this->belongsTo(Lekarnicky::class, 'lekarnicky_id');
    }

    public function material()
    {
        return $this->belongsTo(LekarnickeMaterial::class, 'material_id');
    }

    public function objednalUser()
    {
        return $this->belongsTo(User::class, 'objednal_user_id');
    }
}
