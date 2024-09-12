<?php

namespace App\Orchid\Screens\File;

use App\Enum\FileStatus;
use App\Jobs\FileImportJob;
use App\Libs\LocaleHelper;
use App\Models\File;
use Illuminate\Http\Request;
use Orchid\Attachment\Models\Attachment;
use Orchid\Screen\Actions\Button;
use Orchid\Screen\Actions\Link;
use Orchid\Screen\Fields\Group;
use Orchid\Screen\Fields\Input;
use Orchid\Screen\Fields\Select;
use Orchid\Screen\Fields\Upload;
use Orchid\Screen\Screen;
use Orchid\Screen\TD;
use Orchid\Support\Facades\Alert;
use Orchid\Support\Facades\Layout;

class FileImportScreen extends Screen
{
    /**
     * Fetch data to be displayed on the screen.
     *
     * @return array
     */
    public function query(): iterable
    {
        return [
            'files' => File::paginate(),
        ];
    }

    /**
     * The name of the screen displayed in the header.
     *
     * @return string|null
     */
    public function name(): ?string
    {
        return 'Files';
    }

    /**
     * The screen's action buttons.
     *
     * @return \Orchid\Screen\Action[]
     */
    public function commandBar(): iterable
    {
        return [
            Button::make('Upload')
                ->icon('upload')
                ->method('upload'),
        ];
    }

    /**
     * The screen's layout elements.
     *
     * @return \Orchid\Screen\Layout[]|string[]
     */
    public function layout(): iterable
    {
        return [
            Layout::rows([
                Group::make([
                    Select::make('country')
                        ->options(app(LocaleHelper::class)->countriesAsOptions())
                        ->title('Country')
                        ->value('US')
                        ->required(),
                    Select::make("language")
                        ->options(app(LocaleHelper::class)->languagesAsOptions())
                        ->title("Language")
                        ->required()
                        ->value("en"),

                    Input::make('source')
                        ->title('Source')
                        ->placeholder('ahref, sermush, ...'),
                    Input::make('field')
                        ->title('Field')
                        ->placeholder('book, music, app, ...'),
                ]),
                Upload::make('file')
                    ->title('File'),
            ]),

            Layout::table('files', [
                TD::make('id'),
                TD::make('name')
                    ->render(function (File $file){
                        return Link::make($file->name)->target('_blank')
                            ->route('platform.keywords.list', [
                                'filter[file_id]' => $file->id,
                            ])->class('text-decoration-underline');
                    }),
                TD::make('meta')
                    ->render(function (File $file) {
                        if ($file->meta){
                            return "<b>".$file->keywords()->count()."/".$file->meta->total_keywords."</b> KW";
                        }else{
                            return "_";
                        }
                    }),
                TD::make('country'),
                TD::make('language')
                    ->render(function (File $file) {
                        return (new LocaleHelper())->language_name($file->language);
                    }),
                TD::make('field')
                    ->render(function (File $file) {
                        return "<b>Source: </b>" . $file->source . "<br><b>Field: </b>" . $file->field;
                    }),
                TD::make('status')
                    ->render(function (File $file) {
                        return FileStatus::search($file->status);
                    }),
                TD::make('Action')
                    ->render(function (File $file) {
                        return Button::make('Data Import')->icon('upload')
                                ->method('import', [
                                    'file_id' => $file->id,
                                ])->class('btn btn-primary my-1')->canSee(!$file->status) .
                            Button::make('Delete')->icon('trash')
                                ->method('delete', [
                                    'file_id' => $file->id,
                                ])->class('btn btn-danger my-1');
                    }),
            ])
        ];
    }

    public function upload(Request $request)
    {
        $file_ids = $request->get('file');
        foreach ($file_ids as $file_id) {
            $attachment = Attachment::find($file_id);
            $file = File::data(
                $attachment->original_name,
                country: $request->get('country'),
                source: $request->get('source'),
                field: $request->get('field'),
                language: $request->get("language"),
            );
            $file->attachment()->sync([$attachment->id], false);
        }

        Alert::success('File imported successfully!');
    }

    public function import(Request $request)
    {
        $file = File::find($request->get('file_id'));
        if ($file){
            FileImportJob::dispatch($file->id);
            Alert::warning('File data importing ...');
        }
    }

    public function delete(Request $request): void
    {
        $file = File::find($request->get('file_id'));
        $file->delete();
        Alert::success('File was deleted!');
    }
}
