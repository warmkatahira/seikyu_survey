<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('choice_options', function (Blueprint $table) {
            $table->id();
            $table->foreignId('choice_category_id')->constrained()->cascadeOnDelete();
            $table->string('value', 50)->nullable();
            $table->string('label');
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['choice_category_id', 'value']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('choice_options');
    }
};
