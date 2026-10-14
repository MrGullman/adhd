<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Where it is kept: kyl, frys, skafferi or annat.
        Schema::table('pantry_items', function (Blueprint $table) {
            $table->string('location', 20)->default('skafferi')->after('unit');
        });

        // What is already there gets the usual place for its kind of grocery.
        $places = [
            'kyl' => ['dairy', 'meat', 'produce'],
            'frys' => ['frozen'],
            'annat' => ['household', 'other'],
        ];

        foreach ($places as $location => $categories) {
            DB::table('pantry_items')
                ->whereIn('ingredient_id', DB::table('ingredients')->whereIn('category', $categories)->select('id'))
                ->update(['location' => $location]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pantry_items', function (Blueprint $table) {
            $table->dropColumn('location');
        });
    }
};
