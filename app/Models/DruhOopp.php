<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;


class DruhOopp extends Model
{
    protected $table = 'druhy_oopp';

    protected $fillable = ['nazev'];

    public function produkty(): HasMany
    {
        return $this->hasMany(Produkt::class, 'druh_id');
    }
}
