<?php

namespace App\Http\Controllers\Samo;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class GuardiaController extends Controller
{
    public function index()
    {
        return view('samo.guardia.index');
    }
}
