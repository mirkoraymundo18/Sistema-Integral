<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MeasurementUnit extends Model
{
    protected $casts = ['permite_decimales' => 'boolean'];

    protected $fillable = ['nombre', 'simbolo', 'permite_decimales'];
}
