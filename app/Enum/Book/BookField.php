<?php

namespace App\Enum\Book;

enum BookField: string
{
    case TITLE = 'title';
    case AUTHOR = 'author';
    case TRANSLATOR = 'translator';
    case PUBLISHER = 'publisher';
    case YEAR = 'year';
    case COUNTRY = 'country';
    case LANGUAGE = 'language';
}
