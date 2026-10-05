<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 基本情報 asks whether the customer checks the invoice before it is sent, answered from the
 * existing はい／いいえ dropdown.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('survey_responses', function (Blueprint $table) {
            $table->foreignId('customer_check_option_id')->nullable()->after('invoice_composition_option_id')
                ->constrained('choice_options')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('survey_responses', function (Blueprint $table) {
            $table->dropConstrainedForeignId('customer_check_option_id');
        });
    }
};
