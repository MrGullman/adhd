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
        Schema::create('ingredients', function (Blueprint $table) {
            $table->id();
            // Null means the shared basic list, kept up in the admin portal.
            $table->foreignId('family_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('name', 50);
            $table->foreignId('picture_id')->nullable()->constrained()->nullOnDelete();
            $table->string('category', 20)->default('other');
            $table->string('default_unit', 10)->nullable();
            $table->timestamps();

            $table->index(['family_id', 'name']);
        });

        Schema::create('recipes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('family_id')->constrained()->cascadeOnDelete();
            $table->string('name', 100);
            $table->foreignId('picture_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedTinyInteger('portions')->default(4);
            $table->unsignedSmallInteger('time_minutes')->nullable();
            $table->text('instructions')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('recipe_ingredients', function (Blueprint $table) {
            $table->id();
            $table->foreignId('recipe_id')->constrained()->cascadeOnDelete();
            $table->foreignId('ingredient_id')->constrained()->cascadeOnDelete();
            $table->decimal('quantity', 8, 2)->nullable();
            $table->string('unit', 10)->nullable();
            $table->string('note', 50)->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });

        // What is at home. No quantity means "finns" (enough).
        Schema::create('pantry_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('family_id')->constrained()->cascadeOnDelete();
            $table->foreignId('ingredient_id')->constrained()->cascadeOnDelete();
            $table->decimal('quantity', 8, 2)->nullable();
            $table->string('unit', 10)->nullable();
            // A staple that is always at home, e.g. salt and oil.
            $table->boolean('always_home')->default(false);
            $table->timestamps();

            $table->unique(['family_id', 'ingredient_id']);
        });

        $this->addBasicList();
    }

    /**
     * Common ingredients so that families can start right away. Kept up in
     * the admin portal from now on.
     */
    private function addBasicList(): void
    {
        $list = [
            'produce' => ['Banan' => 'st', 'Äpple' => 'st', 'Apelsin' => 'st', 'Citron' => 'st', 'Gurka' => 'st', 'Tomat' => 'st', 'Körsbärstomater' => 'g', 'Paprika' => 'st', 'Lök' => 'st', 'Rödlök' => 'st', 'Vitlök' => 'klyfta', 'Morot' => 'st', 'Potatis' => 'g', 'Sötpotatis' => 'g', 'Broccoli' => 'g', 'Blomkål' => 'g', 'Isbergssallad' => 'st', 'Spenat' => 'g', 'Avokado' => 'st', 'Champinjoner' => 'g', 'Majs' => 'g', 'Purjolök' => 'st', 'Ingefära' => 'g', 'Färsk basilika' => 'kruka', 'Persilja' => 'kruka'],
            'bread' => ['Bröd' => 'st', 'Knäckebröd' => 'paket', 'Tortillabröd' => 'st', 'Hamburgerbröd' => 'st', 'Pitabröd' => 'st'],
            'dairy' => ['Mjölk' => 'l', 'Filmjölk' => 'l', 'Yoghurt' => 'l', 'Smör' => 'g', 'Ost' => 'g', 'Riven ost' => 'g', 'Mozzarella' => 'g', 'Fetaost' => 'g', 'Ägg' => 'st', 'Grädde' => 'dl', 'Matlagningsgrädde' => 'dl', 'Crème fraiche' => 'dl', 'Gräddfil' => 'dl', 'Kvarg' => 'g', 'Kokosmjölk' => 'ml'],
            'meat' => ['Köttfärs' => 'g', 'Kycklingfilé' => 'g', 'Kycklingfärs' => 'g', 'Fläskfilé' => 'g', 'Bacon' => 'g', 'Korv' => 'g', 'Falukorv' => 'g', 'Skinka' => 'g', 'Laxfilé' => 'g', 'Torskfilé' => 'g', 'Räkor' => 'g', 'Köttbullar' => 'g'],
            'pantry' => ['Pasta' => 'g', 'Spaghetti' => 'g', 'Ris' => 'dl', 'Nudlar' => 'g', 'Mjöl' => 'dl', 'Socker' => 'dl', 'Havregryn' => 'dl', 'Krossade tomater' => 'förp', 'Tomatpuré' => 'msk', 'Passerade tomater' => 'förp', 'Kidneybönor' => 'förp', 'Kikärtor' => 'förp', 'Röda linser' => 'dl', 'Buljongtärning' => 'st', 'Olivolja' => 'msk', 'Rapsolja' => 'msk', 'Salt' => 'krm', 'Svartpeppar' => 'krm', 'Tacokrydda' => 'påse', 'Curry' => 'tsk', 'Paprikapulver' => 'tsk', 'Oregano' => 'tsk', 'Soja' => 'msk', 'Ketchup' => 'msk', 'Senap' => 'msk', 'Majonnäs' => 'msk', 'Tacosås' => 'burk', 'Sylt' => 'msk', 'Honung' => 'msk', 'Müsli' => 'dl', 'Bakpulver' => 'tsk', 'Jäst' => 'g'],
            'frozen' => ['Frysta ärtor' => 'g', 'Fryst spenat' => 'g', 'Fiskpinnar' => 'st', 'Pommes frites' => 'g', 'Frysta bär' => 'dl', 'Glass' => 'l'],
            'drinks' => ['Juice' => 'l', 'Saft' => 'dl', 'Kaffe' => 'paket', 'Te' => 'paket'],
            'household' => ['Toalettpapper' => 'paket', 'Hushållspapper' => 'paket', 'Diskmedel' => 'st', 'Tvättmedel' => 'st', 'Tandkräm' => 'st'],
        ];

        $now = now();
        $rows = [];

        foreach ($list as $category => $items) {
            foreach ($items as $name => $unit) {
                $rows[] = ['family_id' => null, 'name' => $name, 'category' => $category, 'default_unit' => $unit, 'created_at' => $now, 'updated_at' => $now];
            }
        }

        DB::table('ingredients')->insert($rows);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pantry_items');
        Schema::dropIfExists('recipe_ingredients');
        Schema::dropIfExists('recipes');
        Schema::dropIfExists('ingredients');
    }
};
