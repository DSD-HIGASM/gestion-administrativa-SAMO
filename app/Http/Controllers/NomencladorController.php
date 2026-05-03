<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class NomencladorController extends Controller
{
    public function index()
    {
        return view('config.nomencladores');
    }
}
