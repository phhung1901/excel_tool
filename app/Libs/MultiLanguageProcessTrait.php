<?php

namespace App\Libs;

use LanguageDetection\Language;
use voku\helper\StopWords;

trait MultiLanguageProcessTrait
{
    protected static $language;

    protected static $stopwords;

    protected static function isLatin($string)
    {
        // chinese
        $pattern = '的|一|是|不|了|人|我|在|有|他|这|中|大|来|上|国|个|到|说|们|为|子|和|你|地|出|道|也|时|年|得|就|那|要|下|以|生|会|自|着|去|之|过|家|学|对|可|她|里|后|小|么|心|多|天|而|能|好|都|然|没|日|于|起|还|发|成|事|只|作|当|想|看|文|无|开|手|十|用|主|行|方|又|如|前|所|本|见|经|头|面|公|同|三|已|老|从|动|两|长';
        $pattern .= "|[\u{3040}-\u{309F}]"; // japanese

        return ! preg_match('/'.$pattern.'/ui', $string);
    }

    protected function getLanguageDetector()
    {
        if (! self::$language) {
            self::$language = new Language();
        }

        return self::$language;
    }

    /**
     * @return StopWords
     */
    protected function getStopwordsGenerator()
    {
        if (! self::$stopwords) {
            self::$stopwords = new StopWords();
        }

        return self::$stopwords;
    }
}
