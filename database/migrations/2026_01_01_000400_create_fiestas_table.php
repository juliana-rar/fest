<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fiestas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('municipio_id')->constrained('municipios')->cascadeOnUpdate()->restrictOnDelete();
            $table->string('nombre');
            $table->string('slug')->unique();
            $table->text('descripcion')->nullable();
            $table->date('fecha_inicio');
            $table->date('fecha_fin');
            $table->string('imagen')->nullable();
            $table->boolean('destacada')->default(false);
            $table->boolean('publicada')->default(true);
            $table->unsignedInteger('visitas')->default(0);
            $table->unsignedInteger('compartidos')->default(0);
            $table->timestamps();

            $table->index(['publicada', 'fecha_inicio', 'fecha_fin']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fiestas');
    }
};
