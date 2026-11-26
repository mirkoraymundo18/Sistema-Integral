<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RealEstateProject extends Model
{
    protected $fillable = ['nombre', 'ubicacion', 'descripcion'];

    public function lots()
    {
        return $this->hasMany(LandLot::class);
    }
}
