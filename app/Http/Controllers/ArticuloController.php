<?php

namespace App\Http\Controllers;

use App\Models\Trabajo;
use App\Models\TrabajoArticulo;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;

class ArticuloController extends Controller
{
    public function trabajo(Trabajo $trabajo)
    {
        $trabajo->load([
            'trabajoArticulos' => function ($query) {
                $query->where('tecnico_id', Auth::id())
                    ->with(['articulo.subCategoria.categoria'])
                    ->orderBy('fecha', 'desc')
                    ->orderBy('hora', 'desc');
            },
            'vehiculo.tipoVehiculo'
        ]);

        return view('articulos.trabajo', compact('trabajo'));
    }

    public function index()
    {
        $startOfWeek = Carbon::now()->startOfWeek();
        $endOfWeek = Carbon::now()->endOfWeek();

        $articulos = TrabajoArticulo::where('tecnico_id', Auth::id())
            ->whereBetween('fecha', [$startOfWeek, $endOfWeek])
            ->with([
                'articulo.subCategoria.categoria',
                'trabajo.vehiculo'
            ])
            ->orderBy('fecha', 'desc')
            ->orderBy('hora', 'desc')
            ->paginate(10);

        return view('articulos.index', compact('articulos'));
    }

    public function confirmarTrabajo(TrabajoArticulo $trabajoArticulo)
    {
        $trabajoArticulo->confirmado = true;
        $trabajoArticulo->save();
        return back()->with('success', 'El artículo ha sido confirmado');
    }

    public function confirmarIndex(TrabajoArticulo $trabajoArticulo)
    {
        $trabajoArticulo->confirmado = true;
        $trabajoArticulo->save();
        return back()->with('success', 'El artículo ha sido confirmado');
    }
}
