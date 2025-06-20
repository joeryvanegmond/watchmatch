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
        Schema::create('watch_similarities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('watch_id')->constrained('watches')->onDelete('cascade');
            $table->foreignId('similar_watch_id')->constrained('watches')->onDelete('cascade');
            $table->decimal('link_strength')->default(0.1);
            $table->timestamps();
        
            // Zorg dat je deze combinatie niet dubbel opslaat
            $table->unique(['watch_id', 'similar_watch_id']);
        });
        
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('watch_similarities');
    }
};
