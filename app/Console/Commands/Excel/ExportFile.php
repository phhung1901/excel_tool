<?php

namespace App\Console\Commands\Excel;

use App\Models\Enum\KeywordStatus;
use App\Models\File;
use App\Models\Keyword;
use App\Services\Excel\Export\KeywordExport;
use App\Services\KeywordService;
use Illuminate\Console\Command;
use Maatwebsite\Excel\Facades\Excel;

class ExportFile extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'file:export
    {--file_id= : File id}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Export final keyword file';

    /**
     * Execute the console command.
     */
    public function handle()
    {
//        Keyword::where('status', '!=', KeywordStatus::SEARCH_SUCCESS)
//            ->where('file_id', $this->option('file_id'))
//            ->update(
//                [
//                    'status' => KeywordStatus::SEARCH_SUCCESS,
//                    'field' => 'app',
//                ]
//            );
        $file = File::find($this->option('file_id'));
        $file_name = $file->attachment()->first()->original_name;
        $this->info('INTENT CHECK...');
        while ($keyword = Keyword::where('status', KeywordStatus::SEARCH_SUCCESS)
            ->where('file_id', $this->option('file_id'))
            ->first())
        {
            $this->warn("\tKeyword: $keyword->keyword ($keyword->pos)");
            KeywordService::checkIntent($keyword);
        }
        $this->info('FINISHED !!!');
        Excel::store(new KeywordExport($file), "final/$file_name", 'local');
    }
}
