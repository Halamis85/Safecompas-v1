<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Zamestnanec extends Model
{
    protected $table = 'zamestnanci';

    protected $fillable = ['jmeno', 'prijmeni', 'stredisko'];

    public function objednavky(): HasMany
    {
        return $this->hasMany(Objednavka::class);
    }

    public function getFullNameAttribute(): string
    {
        return "{$this->jmeno} {$this->prijmeni}";
    }
}
