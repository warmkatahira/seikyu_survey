<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Ticking その他 in a multi-select now asks what it is; the answer is kept on that tick.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('survey_response_choices', function (Blueprint $table) {
            $table->string('other_text', 100)->nullable()->after('choice_option_id')
                ->comment('What その他 means, on the tick of the その他 option only');
        });
    }

    public function down(): void
    {
        Schema::table('survey_response_choices', function (Blueprint $table) {
            $table->dropColumn('other_text');
        });
    }
};
