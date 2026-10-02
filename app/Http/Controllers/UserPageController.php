<?php

namespace App\Http\Controllers;

use Illuminate\View\View;

class UserPageController extends Controller
{
    public function index(): View
    {
        return view('users.index');
    }

    public function create(): View
    {
        return view('users.form', ['id' => null]);
    }

    public function edit(int $id): View
    {
        return view('users.form', ['id' => $id]);
    }
}
