<?php


namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Lekarnicky extends Model
{
    use HasFactory;

    protected $table = 'lekarnicke';

    protected $fillable = [
        'nazev',
        'umisteni',
        'zodpovedna_osoba',
        'popis',
        'status',
        'posledni_kontrola',
        'dalsi_kontrola'
    ];

    protected $casts = [
        'posledni_kontrola' => 'date',
        'dalsi_kontrola' => 'date',
    ];

    public function material()
    {
        return $this->hasMany(LekarnickeMaterial::class, 'lekarnicky_id');
    }

    public function urazy()
    {
        return $this->hasMany(Uraz::class, 'lekarnicky_id');
    }

    public function getExpirujiciMaterialAttribute()
    {
        return $this->material()
            ->where('datum_expirace', '<=', Carbon::now()->addDays(30))
            ->where('datum_expirace', '>=', Carbon::now())
            ->get();
    }

    public function getNizkyStavMaterialAttribute()
    {
        return $this->material()
            ->whereRaw('aktualni_pocet <= minimalni_pocet')
            ->get();
    }

    public function getJePotrebaKontrolaAttribute()
    {
        return $this->dalsi_kontrola && $this->dalsi_kontrola <= Carbon::now()->addDays(7);
    }
}
