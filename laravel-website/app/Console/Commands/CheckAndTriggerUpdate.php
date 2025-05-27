<?php

namespace App\Console\Commands;

use App\Models\UpdateSetting;
use App\Services\RabbitService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

class CheckAndTriggerUpdate extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:check-and-trigger-update';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $setting = UpdateSetting::first();
        if (!$setting) {
            $this->info('No update setting found.');
            return;
        }

        $now = Carbon::now();
        $last = $setting->last_updated_at ? Carbon::parse($setting->last_updated_at) : null;

        $shouldUpdate = false;

        switch ($setting->interval) {
            case 'daily':
                if (!$last || $last->diffInDays($now, false) >= 1)
                    $shouldUpdate = true;
                break;
            case 'weekly':
                if (!$last || $last->diffInWeeks($now, false) >= 1)
                    $shouldUpdate = true;
                break;
            case 'monthly':
                if (!$last || $last->diffInMonths($now, false) >= 1)
                    $shouldUpdate = true;
                break;
            case 'quarterly':
                if (!$last || $last->diffInMonths($now, false) >= 3)
                    $shouldUpdate = true;
                break;
            case 'yearly':
                if (!$last || $last->diffInYears($now, false) >= 1)
                    $shouldUpdate = true;
                break;
        }

        if ($shouldUpdate) {
            RabbitService::sendDownloadTask();

            // Update last_updated_at
            // $setting->last_updated_at = $now;
            // $setting->save();

            $this->info('Update task sent and last_updated_at updated.');
        } else {
            $this->info('Not time to update yet.');
        }
    }
}
