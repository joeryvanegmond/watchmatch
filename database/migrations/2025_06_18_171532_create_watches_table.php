<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('watches', function (Blueprint $table) {
            $table->id();
            $table->string('brand');             // Titel van het horloge
            $table->string('model'); // Korte omschrijving
            $table->text('variant')->nullable(); // Korte omschrijving
            $table->text('url')->nullable();   // Link naar de bron
            $table->text('image_url')->nullable(); // Afbeeldingslink
            $table->decimal('price', 10, 2)->nullable(); // Optionele prijs
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('watches');
    }
};
