<?php

namespace App\Models;

use App\Services\TrabajoService;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TrabajoArticulo extends Model
{
    use HasFactory;

    protected $fillable = [
        'despacho_id',
        'fecha',
        'hora',
        'trabajo_id',
        'articulo_id',
        'precio',
        'cantidad',
        'tecnico_id',
        'responsable_id',
        'movimiento',
        'observacion',
        'confirmado',
    ];

    protected $casts = [
        'fecha' => 'date',
        'hora' => 'datetime',
    ];

    public function articulo()
    {
        return $this->belongsTo(Articulo::class, 'articulo_id')->withTrashed();
    }

    public function trabajo()
    {
        return $this->belongsTo(Trabajo::class, 'trabajo_id')->withTrashed();
    }

    public function tecnico()
    {
        return $this->belongsTo(User::class, 'tecnico_id')->withTrashed();
    }

    public function responsable()
    {
        return $this->belongsTo(User::class, 'responsable_id')->withTrashed();
    }

    public function despacho()
    {
        return $this->belongsTo(Despacho::class);
    }

    protected static function booted()
    {
        static::saved(function ($trabajoArticulo) {
            $trabajo = $trabajoArticulo->trabajo;
            TrabajoService::actualizarTrabajoPorId($trabajo);
        });

        static::deleted(function ($trabajoArticulo) {
            $trabajo = $trabajoArticulo->trabajo;
            TrabajoService::actualizarTrabajoPorId($trabajo);
        });
    }
}
