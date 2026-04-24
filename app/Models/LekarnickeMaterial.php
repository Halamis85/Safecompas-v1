<?php


namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LekarnickeMaterial extends Model
{
    use HasFactory;

    protected $table = 'lekarnicky_material';

    protected $fillable = [
        'lekarnicky_id',
        'nazev_materialu',
        'typ_materialu',
        'aktualni_pocet',
        'minimalni_pocet',
        'maximalni_pocet',
        'jednotka',
        'datum_expirace',
        'cena_za_jednotku',
        'dodavatel',
        'poznamky'
    ];

    protected $casts = [
        'datum_expirace' => 'date',
        'cena_za_jednotku' => 'decimal:2',
    ];

    public function lekarnicky()
    {
        return $this->belongsTo(Lekarnicky::class, 'lekarnicky_id');
    }

    public function vydejMaterialu()
    {
        return $this->hasMany(VydejMaterialu::class, 'material_id');
    }

    public function getJeExpirovanyAttribute()
    {
        return $this->datum_expirace && $this->datum_expirace < now();
    }

    public function getExpirujeBrzyAttribute()
    {
        return $this->datum_expirace &&
            $this->datum_expirace >= now() &&
            $this->datum_expirace <= now()->addDays(30);
    }

    public function getJeNizkyStavAttribute()
    {
        return $this->aktualni_pocet <= $this->minimalni_pocet;
    }
}
