<?php

namespace App\Libs;

use Illuminate\Support\Collection;

class KeywordsList
{
    /** @var array */
    protected $items;

    protected $language;

    /**
     * KeywordsList constructor.
     *
     * @param  array|Collection  $items
     */
    public function __construct($items, $language = '')
    {
        $this->items = $items;
        $this->language = $language;
    }

    public function getLanguage()
    {
        return $this->language;
    }

    public function toArray($raw = false)
    {
        if ($raw) {
            return [
                'language' => $this->getLanguage(),
                'items' => $this->items,
            ];
        } else {
            return array_keys($this->items);
        }
    }
}
