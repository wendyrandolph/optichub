<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('proposal_payment_schedules', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('proposal_id');
            $table->string('label')->nullable();
            $table->decimal('amount', 12, 2)->nullable();
            $table->string('due_trigger')->nullable();
            $table->text('note')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['tenant_id', 'proposal_id']);
            $table->foreign('proposal_id')->references('id')->on('proposals')->onDelete('cascade');
        });

        // Backfill existing JSON schedules into rows.
        $proposals = DB::table('proposals')
            ->select('id', 'tenant_id', 'payment_schedule')
            ->whereNotNull('payment_schedule')
            ->get();

        foreach ($proposals as $proposal) {
            $schedule = json_decode($proposal->payment_schedule, true);
            if (!is_array($schedule)) {
                continue;
            }
            $order = 0;
            foreach ($schedule as $row) {
                if (!is_array($row)) {
                    continue;
                }
                $label = $row['label'] ?? null;
                $amount = $row['amount'] ?? null;
                $due = $row['due_trigger'] ?? null;
                $note = $row['note'] ?? null;
                if (empty($label) && ($amount === null || $amount === '')) {
                    continue;
                }
                DB::table('proposal_payment_schedules')->insert([
                    'tenant_id' => $proposal->tenant_id,
                    'proposal_id' => $proposal->id,
                    'label' => $label,
                    'amount' => $amount,
                    'due_trigger' => $due,
                    'note' => $note,
                    'sort_order' => $order,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                $order++;
            }
            DB::table('proposals')
                ->where('id', $proposal->id)
                ->update(['payment_schedule' => null]);
        }

        DB::table('proposal_payment_schedules')
            ->whereNull('label')
            ->orWhereNull('amount')
            ->delete();
    }

    public function down(): void
    {
        Schema::dropIfExists('proposal_payment_schedules');
    }
};
