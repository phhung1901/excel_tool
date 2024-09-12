<?php
namespace App\Enum;

enum SourceUrlType: string
{
    use EnumTrait;
    case PLATFORM_SERP = 'platform_serp';
    case PLATFORM_LIST = 'platform_list';
    case PLATFORM_PRODUCT = 'platform_product';
}
