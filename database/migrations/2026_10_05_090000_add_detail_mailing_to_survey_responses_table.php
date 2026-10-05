<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 明細について asks whether the 明細 is sent by post, answered from the existing はい／いいえ
 * dropdown.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('survey_responses', function (Blueprint $table) {
            $table->foreignId('detail_mailing_option_id')->nullable()->after('detail_format_other')
                ->constrained('choice_options')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('survey_responses', function (Blueprint $table) {
            $table->dropConstrainedForeignId('detail_mailing_option_id');
        });
    }
};
