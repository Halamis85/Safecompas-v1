<?php


namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VydejMaterialu extends Model
{
    use HasFactory;

    protected $table = 'vydej_materialu';

    protected $fillable = [
        'uraz_id',
        'material_id',
        'vydane_mnozstvi',
        'jednotka',
        'datum_vydeje',
        'osoba_vydavajici',
        'poznamky'
    ];

    protected $casts = [
        'datum_vydeje' => 'datetime',
    ];

    public function uraz()
    {
        return $this->belongsTo(Uraz::class, 'uraz_id');
    }

    public function material()
    {
        return $this->belongsTo(LekarnickeMaterial::class, 'material_id');
    }
}
