<?php

namespace App\Libs;

class Img
{
    protected static $instance = null;

    protected $none_mirror = [
        'http://abc.xyz/sadsad',
    ];
    protected $prefix = 'https://img-ap.123doks.com/mirror';
    protected function __construct()
    {
    }

    protected static function getInstance()
    {
        if(!self::$instance){
            self::$instance = new self;
        }
        return self::$instance;
    }

    public static function mirror(string $url): string
    {
        $instance = self::getInstance();
        foreach ($instance->none_mirror as $value){
            if(str_contains($url, $value)){
                return $url;
            }
        }
        return $instance->prefix . "?url=" . $url;
    }

}
