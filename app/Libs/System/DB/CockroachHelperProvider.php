<?php

namespace App\Libs\System\DB;

use Illuminate\Support\Fluent;
use Illuminate\Support\ServiceProvider;
use YlsIdeas\CockroachDb\Schema\CockroachGrammar;

class CockroachHelperProvider extends ServiceProvider
{
    public function boot()
    {
        CockroachGrammar::macro('typeArray', fn (Fluent $column) => $column->get('value_type').' ARRAY');
    }
}
