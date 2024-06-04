<?php

namespace App\Console\Commands\Keywords;

use App\Models\Enum\FileStatus;
use App\Models\File;
use App\Models\Keyword;
use GuzzleHttp\Client;
use Illuminate\Console\Command;
use Mockery\Exception;
use ONGR\ElasticsearchDSL\Query\Specialized\MoreLikeThisQuery;

class PosTaggingKeyword extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'keyword:pos
    {--file_id= : File id}
    {id?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'POS tagging keyword';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $file = File::find($this->option('file_id'));
        $file->status(FileStatus::POS_RUNNING);
        $keywords = Keyword::when($this->argument('id'), function ($query) {
                return $query->where('id', $this->argument('id'));
            })
            ->where('file_id', $file->id)
            ->get();

        foreach ($keywords as $keyword) {
            $this->info("KEYWORD: $keyword->keyword");
            $retry = 0;
            retry:
            try {
                $keyword_stemmer = $this->stemmer($keyword->keyword, $file->country);
                $pos = Keyword::genPOS($keyword_stemmer, $file->country);
                $keyword->pos = $pos;
                $keyword->save();
                $this->warn("====>POS: $pos");
            }catch (Exception $e) {
                $retry++;
                if ($retry >= 2){
                    $this->error($e->getMessage());
                }
                sleep(15);
                goto retry;
            }
        }
        $file->status(FileStatus::POS_FINISHED);
    }

    public function stemmer($keyword, $country)
    {
        $client = new Client();

        $country_filter = match ($country) {
            'es' => ['lowercase', 'spanish_stemmer'],
            default => ['lowercase'],
        };

        $response = $client->request('GET', 'http://localhost:9200/keywords_pos_index/_analyze', [
            'headers' => [
                'Content-Type' => 'application/json',
            ],
            'json' => [
                'tokenizer' => 'standard',
                'filter' => $country_filter,
                'text' => $keyword
            ],
        ]);

        $data = json_decode($response->getBody(), true);

        $keyword = "";
        foreach (array_column($data['tokens'], 'token') as $token){
            $keyword .= $token." ";
        }
        return trim($keyword);
    }
}
