<?php
/**
 * Created by PhpStorm.
 * User: hocvt
 * Date: 2020-01-21
 * Time: 23:07
 */

namespace App\Libs;

use App\Models\Topic;
use App\SmartResource\Entities\TopicData;

class LazyTopicMatching
{
    public static function getTopic(array $tags, $topics): null|Topic|TopicData
    {
        $tags = self::toLowerCase($tags);
        $other_topic = null;
        /** @var TopicData $topic */
        foreach ($topics as $topic) {
            $topic_in_current_locale = trans('topics.name.'.$topic->language_key);
            $names = $topic->name;
            if ($topic_in_current_locale != $topic->name) {
                $names .= ' & '.$topic_in_current_locale;
            }
            $names = self::toLowerCase(explode(' & ', $names));
            if (count(array_intersect($names, $tags))) {
                return $topic;
            }
            if ($topic->language_key == 'other') {
                $other_topic = $topic;
            }
        }

        return $other_topic;
    }

    protected static function toLowerCase($strings = []): array
    {
        foreach ($strings as &$string) {
            $string = mb_strtolower($string);
        }

        return $strings;
    }
}
