<?php

namespace App\Http\Controllers;

use Illuminate\View\View;

class TerminalPageController extends Controller
{
    public function index(): View
    {
        return view('terminal.index');
    }
}
