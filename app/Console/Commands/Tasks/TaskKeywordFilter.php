<?php

namespace App\Console\Commands\Tasks;

use App\Enum\KeywordStatus;
use App\Models\Keyword;
use App\Services\KeywordService;
use Crwlr\Crawler\Logger\CliLogger;
use Illuminate\Console\Command;

class TaskKeywordFilter extends Command
{
    public CliLogger $cli;
    public KeywordService $service;
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'task:kw:filter
    {--file= : File ID}
    {--field= : File field}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    public function __construct(){
        parent::__construct();
        $this->cli = new CliLogger();
        $this->service = new KeywordService();
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        while (
            $keyword = Keyword::when($this->option('file'), function ($query) {
                $query->where('file_id', $this->option('file'));
            })
                ->when($this->option('field'), function ($query) {
                    $query->where('field', $this->option('field'));
                })
                ->where('task_filter', KeywordStatus::IMPORTED)
                ->first()
        )
        {
            $this->cli->info("[Filtering] {$keyword->keyword}");
            if (!$this->service->keywordFilter($keyword)) $keyword->delete();
            else{
                $keyword->task_filter = KeywordStatus::ORIGIN_KEYWORD;
                $keyword->save();
            };
            sleep(5);
        }
    }
}
