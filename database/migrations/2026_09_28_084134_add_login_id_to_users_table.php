<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Accounts are shared and handed out by the office, so people sign in with a short
     * login id instead of a mailbox. The email column stays for future notifications
     * but is no longer required.
     *
     * The column is added nullable and backfilled before the unique index goes on, so
     * the migration also works against a users table that already has rows.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('login_id', 50)->nullable()->after('id');
            $table->string('email')->nullable()->change();
        });

        $this->backfillLoginIds();

        Schema::table('users', function (Blueprint $table) {
            $table->string('login_id', 50)->nullable(false)->change();
            $table->unique('login_id');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['login_id']);
            $table->dropColumn('login_id');
        });
    }

    /**
     * Derives a login id from the local part of each existing address, falling back to
     * the row id whenever that would be empty or already taken.
     */
    private function backfillLoginIds(): void
    {
        $taken = [];

        DB::table('users')->orderBy('id')->chunkById(100, function (iterable $users) use (&$taken): void {
            foreach ($users as $user) {
                $candidate = Str::of((string) $user->email)->before('@')->trim()->limit(50, '')->value();

                if ($candidate === '' || isset($taken[$candidate])) {
                    $candidate = 'user'.$user->id;
                }

                $taken[$candidate] = true;

                DB::table('users')->where('id', $user->id)->update(['login_id' => $candidate]);
            }
        });
    }
};
