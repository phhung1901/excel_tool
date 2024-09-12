<?php

namespace App\Libs;

use Illuminate\Http\Request;

use function Annexare\Countries\countries;
use function Annexare\Countries\languagesAll;

class LocaleHelper
{
    const S_KEY_LANGUAGE = 'current_language';

    const S_KEY_LANGUAGE_SET_TYPE = 'current_language_set_type';

    const S_KEY_COUNTRY = 'current_country';

    const S_KEY_COUNTRY_SET_TYPE = 'current_country_set_type';

    const SET_TYPE_AUTO = 'auto'; // he thong tu detect

    const SET_TYPE_USER = 'user'; // nguoi dung lua chon

    protected $continents = [
        'AF' => 'Africa',
        'AN' => 'Antarctica',
        'AS' => 'Asia',
        'EU' => 'Europe',
        'NA' => 'North America',
        'OC' => 'Oceania',
        'SA' => 'South America',
    ];

    protected $countries;

    protected $languages;

    protected $supported_languages = [
        'ar-AE',
        'bn',
        'cs',
        'de',
        'da',
        'el',
        'en',
        'es-MX',
        'fa-IR',
        'fi',
        'fr',
        'hu',
        'id',
        'it',
        'ja',
        'kk',
        'ko',
        'ms',
        'nl',
        'no',
        'pl',
        'pt-BR',
        'ro',
        'ru',
        'sv',
        'si',
        'th',
        'tl-PH',
        'tr',
        'zh-TW',
        'uk',
        'vi',
    ];

    public function __construct()
    {
        $this->countries = collect(countries());
        $this->languages = collect(languagesAll());
    }

    public function languagesAsOptions($multidimensional = false)
    {
        if ($multidimensional) {
            return $this->languages->mapWithKeys(
                fn ($l, $key) => [
                    $key => [
                        'value' => $key,
                        'label' => $l['name'].' - '.$l['native'],
                    ],
                ]
            )->values();
        } else {
            return $this->languages->map(fn ($l) => $l['name'].' - '.$l['native']);
        }
    }

    public function countriesAsOptions($multidimensional = false)
    {
        if ($multidimensional) {
            return $this->countries->mapWithKeys(
                fn ($l, $key) => [
                    $key => [
                        'value' => $key,
                        'label' => $l['name'].' - '.$l['native'],
                    ],
                ]
            )->values();
        } else {
            return $this->countries->map(fn ($l) => $l['name'].' - '.$l['native']);
        }
    }

    public function phoneCodesAsOptions($multidimensional = false)
    {
        $result = [];
        $this->countries->each(function ($l) use (&$result) {
            if (str_contains(',', $l['phone'])) {
                $phones = explode(',', $l['phone']);
            } else {
                $phones = [$l['phone']];
            }
            foreach ($phones as $phone) {
                $result[$phone] = [
                    'value' => $phone,
                    'label' => '+'.$phone.' ('.$l['name'].')',
                ];
            }
        });

        if (! $multidimensional) {
            return array_map(fn ($l) => $l['label'], $result);
        }

        return array_values($result);
    }

    public function countryInfo($country_code): ?array
    {
        $country_code = strtoupper($country_code);

        return $this->countries->get($country_code);
    }

    public function countryName($country_code)
    {
        $countryInfor = $this->countryInfo($country_code);
        if ($countryInfor) {
            return $countryInfor['name'];
        }

        return '';
    }

    public function setSsLanguage($language, $set_type): void
    {
        if (! in_array($language, $this->supported_languages)) {
            return;
        }
        \Session::put(self::S_KEY_LANGUAGE, $language);
        \Session::put(self::S_KEY_LANGUAGE_SET_TYPE, $set_type);
    }

    public function setSsCountry($country, $set_type): void
    {
        \Session::put(self::S_KEY_COUNTRY, $country);
        \Session::put(self::S_KEY_COUNTRY_SET_TYPE, $set_type);
    }

    public function ssLanguage(): ?string
    {
        return \Session::get(self::S_KEY_LANGUAGE);
    }

    public function ssLanguageSetType(): ?string
    {
        return \Session::get(self::S_KEY_LANGUAGE_SET_TYPE);
    }

    public function ssLanguageSetAuto(): bool
    {
        return \Session::get(self::S_KEY_LANGUAGE_SET_TYPE) != self::SET_TYPE_USER;
    }

    public function ssCountry(): ?string
    {
        return \Session::get(self::S_KEY_COUNTRY);
    }

    public function ssCountrySetType(): ?string
    {
        return \Session::get(self::S_KEY_COUNTRY_SET_TYPE);
    }

    public function setCurrentLanguage(Request $request): void
    {
        $user_language = \Auth::check() ? \Auth::user()->language : '';
        $session_language = $this->ssLanguage();
        //        $system_language = \App::getLocale();
        if ($this->ssLanguageSetAuto() && $user_language) {
            $current_language = $user_language;
        } else {
            $current_language = $session_language ?: config('app.locale');
        }
        //        dd(config('app.locale'));
        //        dd($user_language, $session_language, $system_language, $this->ssLanguageSetAuto(), $current_language, $current_language != $system_language);
        //        if ($current_language != $system_language) {
        \App::setLocale($current_language);
        //        }
    }

    public function suggestedLanguages(): array
    {
        $suggested_languages = explode(',', config('app.supported_locale'));
        $all_languages = config('supported_languages.all');

        return array_filter($all_languages, fn ($lang) => in_array($lang['code'], $suggested_languages));
    }

    public function allSupportedLanguages(bool $ignore_suggested = true): array
    {
        if ($ignore_suggested) {
            $ignored = explode(',', config('app.supported_locale'));
        } else {
            $ignored = [];
        }
        $all_languages = config('supported_languages.all');
        $ignored_count = count($ignored);

        return array_filter($all_languages, fn ($lang) => $ignored_count == 0 || ! in_array($lang['code'], $ignored));
    }

    public function language_info($language_code, $default = null): ?array
    {
        $language_code = preg_replace("/\-.*/", '', $language_code);

        return $this->languages->get($language_code, $default);
    }

    public function language_name($language_code, $default = null): ?string
    {
        $language = $this->language_info($language_code);
        if ($language) {
            return $language['name'];
        } else {
            return $default;
        }
    }

    public function language_native($language_code, $default = null): ?string
    {
        $language = $this->language_info($language_code);
        if ($language) {
            return $language['native'];
        } else {
            return $default;
        }
    }

    public function stop_words($language_code, $default = '_english_'): ?string
    {
        $language_name = $this->language_name($language_code);
        if ($language_name) {
            return '_'.$language_name.'_';
        } else {
            return $default;
        }
    }
}
