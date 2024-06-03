<?php

namespace App\Console\Commands\Keywords;

use App\Models\Enum\FileStatus;
use App\Models\Enum\KeywordStatus;
use App\Models\File;
use App\Models\Keyword;
use App\Services\KeywordService;
use App\Services\SearxService\SearxClient;
use Illuminate\Console\Command;

class SerpKeyword extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'keyword:serp
    {--file_id= : File id}
    {id?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'SERP Keyword';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $file = File::find($this->option('file_id'));
        $file->status(FileStatus::SERP_RUNNING);
        while ($keywords = Keyword::select('id', 'keyword')
            ->where('file_id', $this->option('file_id'))
            ->where('status', KeywordStatus::IMPORTED)
            ->limit(15)->get()->toArray())
        {
            $search_client = new SearxClient();
            $search_results = $search_client->searchMultiple('google', $keywords);

            foreach ($search_results as $id => $result){
                if (count($result)){
                    $keyword = Keyword::find($id);
                    $keyword->search_results = $result;
                    $keyword->status = KeywordStatus::SEARCH_SUCCESS;
                    $keyword->save();
                    $this->info($keyword->keyword." search successful !");
                }
            }
        }
        $file->status(FileStatus::SERP_FINISHED);
    }
}
