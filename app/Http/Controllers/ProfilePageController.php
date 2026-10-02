<?php

namespace App\Http\Controllers;

use Illuminate\View\View;

class ProfilePageController extends Controller
{
    public function edit(): View
    {
        return view('profile.edit');
    }
}
