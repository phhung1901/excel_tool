<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Artisan;
use romanzipp\QueueMonitor\Traits\IsMonitored;

class FileImportJob implements ShouldQueue
{
    public string $file_id;
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    use IsMonitored;

    /**
     * Create a new job instance.
     */
    public function __construct($file_id)
    {
        $this->file_id = $file_id;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Artisan::call('file:import', ['--file_id' => $this->file_id]);
    }
}
