<?php

namespace App\Libs;

class FigureUrlHelper
{
    public static function v2_cover($app_name, $ds_app_id, $ext = 'webp', $host = ''): string
    {
        $id_path = IdToPath::make($ds_app_id, '', 2);

        return self::getHost($host)."/thumbv2/$app_name/$id_path/cover.$ext";
    }

    public static function v2_thumb($app_name, $ds_app_id, $page, $width, $xtop, $xbottom, $ytop, $ybottom, $slug = 'image-figure-table', $ext = 'webp', $host = ''): string
    {
        $id_path = IdToPath::make($ds_app_id, '', 2);
        $figure_info = implode('.', [$page, $width, $xtop, $xbottom, $ytop, $ybottom]);

        return self::getHost($host)."/thumbv2/$app_name/$id_path/$figure_info/$slug.$ext";
    }

    /**
     * Current version
     */
    public static function cover($app_name, $ds_id, $ds_app_id, $ext = 'webp', $host = ''): string
    {
        $id_path = ($ds_id ?: '0').'.'.($ds_app_id ?: '0');

        return self::getHost($host)."/thumbv2/$app_name/$id_path/cover.$ext";
    }

    /**
     * Current version
     */
    public static function thumb($app_name, $ds_id, $ds_app_id, $page, $width, $xtop, $xbottom, $ytop, $ybottom, $slug = 'image-figure-table', $ext = 'webp', $host = ''): string
    {
        $id_path = ($ds_id ?: '0').'.'.($ds_app_id ?: '0');
        $figure_info = implode('.', [$page, $width, $xtop, $xbottom, $ytop, $ybottom]);

        return self::getHost($host)."/thumbv2/$app_name/$id_path/$figure_info/$slug.$ext";
    }

    protected static function getHost($host = ''): string
    {
        if (! $host) {
            $host = config('app.thumb_server') ?: '';
        }

        return rtrim($host, '/');
    }
}
