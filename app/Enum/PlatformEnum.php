<?php

namespace App\Enum;

enum PlatformEnum: string
{
    case NONE = 'none';
    case WORDPRESS_JSON_V2 = 'wp_json_v2';
    case SIMPLE_REST = 'simple_rest';
}
