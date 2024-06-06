<?php

namespace App\Console\Commands\Test;

use App\Models\Enum\KeywordStatus;
use App\Models\Keyword;
use Illuminate\Console\Command;

class KeywordTest extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:keyword
    {--file_id= : File id}';

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
        while ($keyword = Keyword::where('file_id', $this->option('file_id'))
            ->where(function ($query) {
                $query->where('status', KeywordStatus::ORIGIN_KEYWORD)
                    ->orWhere('status', KeywordStatus::DUPLICATE_KEYWORD);
            })
            ->first()
    ) {
            $this->info($keyword->keyword);
            $keyword->status = KeywordStatus::SEARCH_SUCCESS;
            $keyword->keyword_intent = null;
            $keyword->save();
        }
    }
}
