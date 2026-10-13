<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Festival types (Festa Major, Barraques, Fira…). A festival can have several.
        Schema::create('tipos', function (Blueprint $table) {
            $table->id();
            $table->string('nombre');
            $table->string('nombre_es')->nullable();
            $table->string('slug')->unique();
            $table->string('color', 7)->default('#FFB703');
            $table->unsignedSmallInteger('orden')->default(0);
            $table->timestamps();
        });

        Schema::create('fiesta_tipo', function (Blueprint $table) {
            $table->foreignId('fiesta_id')->constrained('fiestas')->cascadeOnDelete();
            $table->foreignId('tipo_id')->constrained('tipos')->cascadeOnDelete();
            $table->primary(['fiesta_id', 'tipo_id']);
            $table->index('tipo_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fiesta_tipo');
        Schema::dropIfExists('tipos');
    }
};
