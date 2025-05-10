<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class UpdateHistorySeeder extends Seeder
{
    public function run(): void
    {
        $updateHistoryId = DB::table('update_history')->insertGetId([
            'status' => 0,
            'started_at' => Carbon::now()->subMinutes(10),
            'description' => 'Contoh proses update awal',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $logs = [];
        for ($i = 1; $i <= 15; $i++) {
            $logs[] = [
                'update_history_id' => $updateHistoryId,
                'message' => "Log message ke-$i",
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        // Menyisipkan log log tersebut
        DB::table('update_logs')->insert($logs);
    }
}
