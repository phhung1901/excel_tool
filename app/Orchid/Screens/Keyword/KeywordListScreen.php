<?php

namespace App\Orchid\Screens\Keyword;

use App\Models\Enum\KeywordStatus;
use App\Models\Keyword;
use Orchid\Screen\Screen;
use Orchid\Screen\TD;
use Orchid\Support\Facades\Layout;

class KeywordListScreen extends Screen
{
    /**
     * Fetch data to be displayed on the screen.
     *
     * @return array
     */
    public function query(): iterable
    {
        return [
            'keywords' => Keyword::with('file')->filters()->defaultSort('id')->paginate(100),
        ];
    }

    /**
     * The name of the screen displayed in the header.
     *
     * @return string|null
     */
    public function name(): ?string
    {
        return 'Keywords';
    }

    /**
     * The screen's action buttons.
     *
     * @return \Orchid\Screen\Action[]
     */
    public function commandBar(): iterable
    {
        return [];
    }

    /**
     * The screen's layout elements.
     *
     * @return \Orchid\Screen\Layout[]|string[]
     */
    public function layout(): iterable
    {
        return [
            Layout::table('keywords', [
                TD::make('id')
                    ->sort()->filter(),
                TD::make('keyword')
                    ->render(function (Keyword $keyword) {
                        $file = $keyword->file;
                        return "<span><b>KW: </b>$keyword->keyword</span>
                                <br><b>S: </b><i>$keyword->slug</i>
                                <br><b>F: </b><i>$file->name</i>";
                    })->sort(),
                TD::make('pos')
                    ->sort(),
                TD::make('raw')
                    ->render(function (Keyword $keyword) {
                        return "
                            <svg xmlns='http://www.w3.org/2000/svg' width='16' height='16' fill='currentColor' class='bi bi-app' viewBox='0 0 16 16'>
                              <path d='M11 2a3 3 0 0 1 3 3v6a3 3 0 0 1-3 3H5a3 3 0 0 1-3-3V5a3 3 0 0 1 3-3zM5 1a4 4 0 0 0-4 4v6a4 4 0 0 0 4 4h6a4 4 0 0 0 4-4V5a4 4 0 0 0-4-4z'/>
                            </svg>
                            $keyword->field <br>
                            <svg xmlns='http://www.w3.org/2000/svg' width='16' height='16' fill='currentColor' class='bi bi-search' viewBox='0 0 16 16'>
                              <path d='M11.742 10.344a6.5 6.5 0 1 0-1.397 1.398h-.001q.044.06.098.115l3.85 3.85a1 1 0 0 0 1.415-1.414l-3.85-3.85a1 1 0 0 0-.115-.1zM12 6.5a5.5 5.5 0 1 1-11 0 5.5 5.5 0 0 1 11 0'/>
                            </svg>
                            {$keyword->raw->volume} <br>
                            <svg xmlns='http://www.w3.org/2000/svg' width='16' height='16' fill='currentColor' class='bi bi-plus-slash-minus' viewBox='0 0 16 16'>
                              <path d='m1.854 14.854 13-13a.5.5 0 0 0-.708-.708l-13 13a.5.5 0 0 0 .708.708M4 1a.5.5 0 0 1 .5.5v2h2a.5.5 0 0 1 0 1h-2v2a.5.5 0 0 1-1 0v-2h-2a.5.5 0 0 1 0-1h2v-2A.5.5 0 0 1 4 1m5 11a.5.5 0 0 1 .5-.5h5a.5.5 0 0 1 0 1h-5A.5.5 0 0 1 9 12'/>
                            </svg>
                            {$keyword->raw->kd}
                        ";
                    }),
                TD::make('status')
                    ->render(function (Keyword $keyword) {
                        return match ($keyword->status) {
                            KeywordStatus::ORIGIN_KEYWORD => '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="#28a745" class="bi bi-check-circle-fill" viewBox="0 0 16 16">
                                                                  <path d="M16 8A8 8 0 1 1 0 8a8 8 0 0 1 16 0m-3.97-3.03a.75.75 0 0 0-1.08.022L7.477 9.417 5.384 7.323a.75.75 0 0 0-1.06 1.06L6.97 11.03a.75.75 0 0 0 1.079-.02l3.992-4.99a.75.75 0 0 0-.01-1.05z"/>
                                                                </svg>',
                            KeywordStatus::DUPLICATE_KEYWORD => '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="#dc3545" class="bi bi-x-circle-fill" viewBox="0 0 16 16">
                                                                  <path d="M16 8A8 8 0 1 1 0 8a8 8 0 0 1 16 0M5.354 4.646a.5.5 0 1 0-.708.708L7.293 8l-2.647 2.646a.5.5 0 0 0 .708.708L8 8.707l2.646 2.647a.5.5 0 0 0 .708-.708L8.707 8l2.647-2.646a.5.5 0 0 0-.708-.708L8 7.293z"/>
                                                                </svg>',
                            KeywordStatus::SEARCH_SUCCESS => '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="#28a745" class="bi bi-google" viewBox="0 0 16 16">
                                                                  <path d="M15.545 6.558a9.4 9.4 0 0 1 .139 1.626c0 2.434-.87 4.492-2.384 5.885h.002C11.978 15.292 10.158 16 8 16A8 8 0 1 1 8 0a7.7 7.7 0 0 1 5.352 2.082l-2.284 2.284A4.35 4.35 0 0 0 8 3.166c-2.087 0-3.86 1.408-4.492 3.304a4.8 4.8 0 0 0 0 3.063h.003c.635 1.893 2.405 3.301 4.492 3.301 1.078 0 2.004-.276 2.722-.764h-.003a3.7 3.7 0 0 0 1.599-2.431H8v-3.08z"/>
                                                                </svg>',
                            default => '_',
                        };
                    }),
                TD::make('intent')
                    ->render(function (Keyword $keyword) {
                        $keyword_intent = $keyword->keyword_intent;
                        if ($keyword_intent) {
                            if (count($keyword_intent['origin'])) {
                                return "<b>ORIGIN ID: {$keyword_intent['origin'][0]['id']}</b><br>
                                <b>KW ORIGIN</b>: {$keyword_intent['origin'][0]['keyword']}";
                            } else {
                                $rs = "<b>KW DUPLICATE</b><br><ul>";
                                foreach ($keyword_intent['duplicate'] as $duplicate) {
                                    $rs .= "<li>{$duplicate['keyword']}</li>";
                                }
                                return $rs . "</ul>";
                            }
                        } else {
                            return "_";
                        }
                    }),
            ])
        ];
    }
}
