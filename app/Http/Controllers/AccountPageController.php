<?php

namespace App\Http\Controllers;

use Illuminate\View\View;

class AccountPageController extends Controller
{
    public function index(): View
    {
        return view('accounts.index');
    }

    public function create(): View
    {
        return view('accounts.form', ['id' => null]);
    }

    public function edit(int $id): View
    {
        return view('accounts.form', ['id' => $id]);
    }
}
