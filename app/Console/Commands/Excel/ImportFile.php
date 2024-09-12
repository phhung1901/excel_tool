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
    {--field=}
    {--rows=10000 : Number of rows to import}';

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
        $field = $this->option('field');
        $rows = $this->option('rows');

        if ($field){
            $files = File::where('field', $field)->get();
            foreach ($files as $file){
                $this->import($file, $rows);
            }
        }else{
            $file = File::find($this->option('file_id'));
            $this->import($file, $rows);
        }
        return self::SUCCESS;
    }

    protected function import(File $file, $rows)
    {
        $file->status(FileStatus::DATA_IMPORTING);
        $import = new KeywordImport($file, $rows);
        Excel::import($import, $file->getPath(), 'public');
        $file->status(FileStatus::DATA_IMPORTED);
        $row_count = $import->getRowCount();
        $file->meta = [
            'row_count' => $row_count,
            'keyword_imported' => Keyword::where('file_id', $file->id)->count(),
        ];
        $file->save();
    }
}
