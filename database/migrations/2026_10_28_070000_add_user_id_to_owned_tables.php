<?php

use App\Enums\RoleName;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Tables whose rows belong to one user; everything else hangs off these. */
    private const TABLES = ['accounts', 'categories', 'goals', 'debts', 'webhooks'];

    public function up(): void
    {
        $ownerId = DB::table('users')->orderBy('id')->value('id');

        foreach (self::TABLES as $table) {
            Schema::table($table, function (Blueprint $table): void {
                $table->foreignId('user_id')->nullable()->after('id')->constrained()->cascadeOnDelete();
            });

            // Everything that existed before belongs to the first user.
            if ($ownerId !== null) {
                DB::table($table)->update(['user_id' => $ownerId]);
            }

            Schema::table($table, function (Blueprint $table): void {
                $table->unsignedBigInteger('user_id')->nullable(false)->change();
            });
        }

        // Category names are unique per user, not across everyone.
        Schema::table('categories', function (Blueprint $table): void {
            $table->dropUnique(['name', 'type']);
            $table->unique(['user_id', 'name', 'type']);
        });

        // Every existing user is a regular user; admins are created on purpose (php artisan admin:create).
        $now = now();
        foreach (RoleName::cases() as $role) {
            DB::table(config('permission.table_names.roles'))->insertOrIgnore([
                'name' => $role->value,
                'guard_name' => 'web',
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        $userRoleId = DB::table(config('permission.table_names.roles'))->where('name', RoleName::User->value)->where('guard_name', 'web')->value('id');

        foreach (DB::table('users')->pluck('id') as $id) {
            DB::table(config('permission.table_names.model_has_roles'))->insertOrIgnore([
                'role_id' => $userRoleId,
                'model_type' => 'App\Models\User',
                config('permission.column_names.model_morph_key') => $id,
            ]);
        }
    }

    public function down(): void
    {
        Schema::table('categories', function (Blueprint $table): void {
            $table->dropUnique(['user_id', 'name', 'type']);
            $table->unique(['name', 'type']);
        });

        foreach (self::TABLES as $table) {
            Schema::table($table, function (Blueprint $table): void {
                $table->dropConstrainedForeignId('user_id');
            });
        }
    }
};
