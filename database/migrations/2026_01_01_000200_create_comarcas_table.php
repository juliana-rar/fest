<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('comarcas', function (Blueprint $table) {
            $table->id();
            $table->string('nombre');
            $table->string('provincia', 20)->index();
            $table->string('slug')->unique();
            $table->string('geojson_id', 10)->nullable()->comment('ICGC comarca code');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('comarcas');
    }
};
