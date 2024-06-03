<?php

namespace App\Jobs;

use App\Models\Enum\FileStatus;
use App\Models\File;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Artisan;

class KeywordSerpJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected File $file;
    /**
     * Create a new job instance.
     */
    public function __construct(File $file)
    {
        $this->file = $file;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $this->file->status(FileStatus::SERP_RUNNING);
        Artisan::call('keyword:serp', ['--file_id' => $this->file->id]);
        $this->file->status(FileStatus::SERP_FINISHED);
        $this->file->status(FileStatus::INTENT_RUNNING);
        Artisan::call('file:export', ['--file_id' => $this->file->id]);
        $this->file->status(FileStatus::INTENT_FINISHED);
    }
}
