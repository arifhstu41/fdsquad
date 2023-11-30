<?php

namespace App\Console\Commands;

use App\Models\Story;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Throwable;

class RemoveExpiredStories extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'remove:expired:stories';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'remove expired stories';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(): int
    {
		$twentyFourHoursAgo = Carbon::now()->subHours(24);

		$stories = Story::where('created_at', '<', $twentyFourHoursAgo)
			->get();

        foreach ($stories as $story) {
            try {
                $story->delete();
            } catch (Throwable $e) {
                Log::error($e->getMessage(), [
                    'code'    => $e->getCode(),
                    'message' => $e->getMessage(),
                    'trace'   => $e->getTrace(),
                    'file'    => $e->getFile(),
                ]);
            }
        }

        return 0;
    }
}
