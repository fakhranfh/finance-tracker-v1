<?php

namespace App\Http\Controllers;

use Illuminate\View\View;

class UserPageController extends Controller
{
    public function index(): View
    {
        $tableHeaders = [
            ['label' => __('users.table.name'), 'sort' => 'name'],
            ['label' => __('users.table.email'), 'sort' => 'email'],
            ['label' => __('users.table.role')],
            ['label' => __('users.table.created_at'), 'sort' => 'created_at'],
            __('users.table.actions'),
        ];

        return view('users.index', ['tableHeaders' => $tableHeaders]);
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
