<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('actos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('fiesta_id')->constrained('fiestas')->cascadeOnDelete();
            $table->date('dia');
            $table->time('hora')->nullable();
            $table->string('titulo');
            $table->string('lugar')->nullable();
            $table->timestamps();

            $table->index(['fiesta_id', 'dia', 'hora']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('actos');
    }
};
