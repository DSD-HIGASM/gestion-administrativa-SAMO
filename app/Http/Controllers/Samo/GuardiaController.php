<?php

namespace App\Http\Controllers\Samo;

use App\Http\Controllers\Controller;
use App\Models\SamoTramite;
use Illuminate\Http\Request;

class GuardiaController extends Controller
{
    public function index()
    {
        return view('samo.guardia.index');
    }

    public function expediente($ulid)
    {
        // Traemos el trámite con todas sus relaciones clave
        $tramite = SamoTramite::with(['paciente', 'atencionGuardia', 'estado'])->findOrFail($ulid);
        return view('samo.guardia.expediente', compact('tramite'));
    }
}
