<?php


namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Uraz extends Model
{
    use HasFactory;

    protected $table = 'urazy';

    protected $fillable = [
        'zamestnanec_id',
        'lekarnicky_id',
        'datum_cas_urazu',
        'popis_urazu',
        'misto_urazu',
        'zavaznost',
        'poskytnutе_osetreni',
        'osoba_poskytujici_pomoc',
        'prevezen_do_nemocnice',
        'poznamky'
    ];

    protected $casts = [
        'datum_cas_urazu' => 'datetime',
        'prevezen_do_nemocnice' => 'boolean',
    ];

    public function zamestnanec()
    {
        return $this->belongsTo(Zamestnanec::class, 'zamestnanec_id');
    }

    public function lekarnicky()
    {
        return $this->belongsTo(Lekarnicky::class, 'lekarnicky_id');
    }

    public function vydejMaterialu()
    {
        return $this->hasMany(VydejMaterialu::class, 'uraz_id');
    }
}
