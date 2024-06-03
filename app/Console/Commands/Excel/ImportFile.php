<?php

namespace App\Console\Commands\Excel;

use App\Models\Enum\FileStatus;
use App\Models\File;
use App\Models\Keyword;
use App\Services\Excel\Import\KeywordImport;
use Illuminate\Console\Command;
use Maatwebsite\Excel\Facades\Excel;

class ImportFile extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'file:import
    {--file_id= File id}
    {--disk=local}
    {--rows=5000 : Number of rows to import}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import file data to DB';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $disk = $this->option('disk');
        $rows = $this->option('rows');

        $file = File::find($this->option('file_id'));
        $file->status(FileStatus::DATA_IMPORTING);
        Excel::import(new KeywordImport($file, $rows), $file->getPath());
        $file->status(FileStatus::DATA_IMPORTED);
        return self::SUCCESS;
    }
}
