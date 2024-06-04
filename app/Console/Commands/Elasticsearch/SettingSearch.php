<?php

namespace App\Console\Commands\Elasticsearch;

use Elastic\Elasticsearch\ClientBuilder;
use Illuminate\Console\Command;

class SettingSearch extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'search:settings';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Add filter setting for ES';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $client = ClientBuilder::create()->build();

        $indexName = 'keywords_pos_index';

        $client->indices()->close(['index' => $indexName]);

        $client->indices()->putSettings([
            'index' => $indexName,
            'body' => [
                'settings' => [
                    'analysis' => [
                        'filter' => [
                            'spanish_stemmer' => [
                                'type' => 'stemmer',
                                'language' => 'light_spanish'
                            ]
                        ],
                        'analyzer' => [
                            'rebuilt_spanish' => [
                                'tokenizer' => 'standard',
                                'filter' => [
                                    'lowercase',
                                    'spanish_stemmer'
                                ]
                            ]
                        ]
                    ]
                ]
            ]
        ]);

        $client->indices()->open(['index' => $indexName]);

        $client->indices()->putMapping([
            'index' => $indexName,
            'body' => [
                'properties' => [
                    'your_field' => [
                        'type' => 'text',
                        'analyzer' => 'rebuilt_spanish'
                    ]
                ]
            ]
        ]);

        $this->info('Elasticsearch settings updated successfully.');
    }
}
