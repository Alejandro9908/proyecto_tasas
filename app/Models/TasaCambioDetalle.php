<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TasaCambioDetalle extends Model
{
    use HasFactory;

    protected $table = 'tasa_cambio_detalles';

    protected $fillable = [
        'tasa_cambio_id',
        'fecha',
        'venta',
        'compra',
    ];

    public function getFechaAttribute($value)
    {
        return \Carbon\Carbon::parse($value)->format('d-m-Y');
    }

    public function tasaCambio(){
        return $this->belongsTo('App\Models\TasaCambio', 'tasa_cambio_id', 'id');
    }
}
