<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('municipios', function (Blueprint $table) {
            $table->id();
            $table->foreignId('comarca_id')->constrained('comarcas')->cascadeOnUpdate()->restrictOnDelete();
            $table->string('nombre');
            $table->string('slug')->unique();
            $table->decimal('lat', 9, 6);
            $table->decimal('lng', 9, 6);
            $table->timestamps();

            $table->index(['lat', 'lng']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('municipios');
    }
};
