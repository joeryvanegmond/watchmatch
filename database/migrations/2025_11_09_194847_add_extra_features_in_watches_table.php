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
        Schema::table('watches', function (Blueprint $table) {
            $table->string('type')->nullable()->after('description'); // bv. Dive watch, Dress watch
            $table->decimal('diameter', 5, 2)->nullable()->after('type'); // in mm
            $table->string('material')->nullable()->after('diameter'); // kastmateriaal
            $table->string('dial_color')->nullable()->after('material'); // kleur wijzerplaat
            $table->string('band_color')->nullable()->after('dial_color'); // optioneel
            $table->string('movement')->nullable()->after('band_color'); // quartz / automatic / manual
            $table->year('year')->nullable()->after('movement'); // introductiejaar
            $table->decimal('water_resistance', 5, 2)->nullable()->after('year'); // bijv. 100.00 voor 100m
            $table->string('gender')->nullable()->after('water_resistance'); // male / female / unisex
            $table->string('style')->nullable()->after('gender'); // modern / vintage / sporty / classic
            $table->float('weight')->nullable()->after('style'); // optioneel, gram
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('watches', function (Blueprint $table) {
            $table->dropColumn([
                'type',
                'diameter',
                'material',
                'dial_color',
                'band_color',
                'movement',
                'year',
                'water_resistance',
                'gender',
                'style',
                'weight',
            ]);
        });
    }
};
