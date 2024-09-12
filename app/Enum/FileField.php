<?php

namespace App\Enum;

use App\Enum\EnumTrait;

enum FileField: string
{
    use EnumTrait;

    case TOP_BOOKS = 'top_books';
    case TOP_APPS = 'top_apps';
    case TOP_GAMES = 'top_games';
    case TOP_SONGS = 'top_songs';
}
