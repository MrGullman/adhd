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
        Schema::create('routines', function (Blueprint $table) {
            $table->id();
            // Null means a template from the admin portal. Families copy templates.
            $table->foreignId('family_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('picture_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name', 50);
            $table->string('day_part', 20)->nullable();
            // Wall-clock times in Sweden, like tasks.
            $table->time('start_time')->nullable();
            $table->time('done_by')->nullable();
            // ISO weekdays, 1 = Monday. Null means every day.
            $table->json('weekdays')->nullable();
            // Fixed order (one step at a time) or free order (a checklist).
            $table->boolean('ordered')->default(true);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('routine_child', function (Blueprint $table) {
            $table->id();
            $table->foreignId('routine_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();

            $table->unique(['routine_id', 'user_id']);
        });

        Schema::create('routine_steps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('routine_id')->constrained()->cascadeOnDelete();
            $table->foreignId('picture_id')->nullable()->constrained()->nullOnDelete();
            $table->string('label', 50);
            $table->unsignedSmallInteger('minutes')->nullable();
            // step, or packlist: "Kolla packningen" shows the day's packing lists.
            $table->string('type', 20)->default('step');
            // Null means every day the routine runs.
            $table->json('weekdays')->nullable();
            $table->boolean('optional')->default(false);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('routine_runs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('family_id')->constrained()->cascadeOnDelete();
            $table->foreignId('routine_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->date('date');
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            // One run per child and day.
            $table->unique(['routine_id', 'user_id', 'date']);
        });

        Schema::create('routine_step_checks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('routine_run_id')->constrained()->cascadeOnDelete();
            $table->foreignId('routine_step_id')->constrained()->cascadeOnDelete();
            $table->timestamp('checked_at');
            $table->boolean('skipped')->default(false);

            $table->unique(['routine_run_id', 'routine_step_id']);
        });

        $this->addTemplates();
    }

    /**
     * Ready-made templates, kept up in the admin portal. Families copy and adapt them.
     */
    private function addTemplates(): void
    {
        $templates = [
            ['Morgonrutin', 'morning', '07:45:00', [
                ['Gå upp', 2, false], ['Kissa', 3, false], ['Klä på dig', 10, false], ['Äta frukost', 15, false],
                ['Borsta tänderna', 3, false], ['Kamma håret', 2, true], ['Kolla packningen', 5, false, 'packlist'], ['Ta på ytterkläder', 5, false],
            ]],
            ['Efter skolan', 'afternoon', null, [
                ['Häng upp jackan', 2, false], ['Packa upp väskan', 3, false], ['Tvätta händerna', 2, false],
                ['Äta mellanmål', 15, false], ['Göra läxor', 20, true],
            ]],
            ['Kvällsrutin', 'evening', null, [
                ['Duka bordet', 5, false], ['Äta middag', 25, false], ['Duka av', 5, false],
                ['Packa väskan till imorgon', 5, false, 'packlist'], ['Lägga fram kläder', 5, true],
            ]],
            ['Läggdags', 'evening', '20:00:00', [
                ['Ta på pyjamas', 5, false], ['Borsta tänderna', 3, false], ['Kissa', 3, false],
                ['Läsa en bok', 15, true], ['Släcka lampan', 1, false],
            ]],
        ];

        $now = now();

        foreach ($templates as [$name, $dayPart, $doneBy, $steps]) {
            $routineId = DB::table('routines')->insertGetId([
                'family_id' => null,
                'name' => $name,
                'day_part' => $dayPart,
                'done_by' => $doneBy,
                'ordered' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            foreach ($steps as $index => $step) {
                DB::table('routine_steps')->insert([
                    'routine_id' => $routineId,
                    'label' => $step[0],
                    'minutes' => $step[1],
                    'optional' => $step[2],
                    'type' => $step[3] ?? 'step',
                    'sort_order' => $index,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('routine_step_checks');
        Schema::dropIfExists('routine_runs');
        Schema::dropIfExists('routine_steps');
        Schema::dropIfExists('routine_child');
        Schema::dropIfExists('routines');
    }
};
