<?php

namespace App\Rules;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Exists;
use Illuminate\Validation\Rules\Unique;

/**
 * Validation rules that only see the signed-in user's rows. `exists:table,id` and `unique:` run plain SQL, so the
 * model scopes that keep users apart never apply to them.
 */
class Owned
{
    /**
     * The id exists in a table that has a user_id column, and is the user's.
     */
    public static function exists(string $table): Exists
    {
        return Rule::exists($table, 'id')->where('user_id', Auth::id());
    }

    /**
     * The id is a record of one of the user's accounts.
     */
    public static function record(): Exists
    {
        return Rule::exists('records', 'id')->where(
            fn ($query) => $query->whereIn('account_id', DB::table('accounts')->where('user_id', Auth::id())->select('id')),
        );
    }

    /**
     * A value that is unique among the user's rows of a table that has a user_id column.
     */
    public static function unique(string $table, string $column): Unique
    {
        return Rule::unique($table, $column)->where('user_id', Auth::id());
    }
}
