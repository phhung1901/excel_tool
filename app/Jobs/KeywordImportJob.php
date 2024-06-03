<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Artisan;

class KeywordImportJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected string $command;
    protected array $options;
    /**
     * Create a new job instance.
     */
    public function __construct(string $command, array $options)
    {
        $this->command = $command;
        $this->options = $options;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Artisan::call($this->command, $this->options);
    }
}
