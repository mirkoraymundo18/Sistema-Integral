<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Client extends Model
{
    protected $fillable = ['dni', 'nombres', 'apellidos', 'telefono', 'email', 'direccion'];

    public function getNombreCompletoAttribute(): string
    {
        return trim($this->nombres.' '.$this->apellidos);
    }

    public function reservations()
    {
        return $this->hasMany(LotReservation::class);
    }
}
