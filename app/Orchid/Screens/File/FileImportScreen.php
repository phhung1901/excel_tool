<?php

namespace App\Orchid\Screens\File;

use App\Jobs\KeywordDataJob;
use App\Jobs\KeywordImportJob;
use App\Jobs\KeywordPosJob;
use App\Jobs\KeywordSerpJob;
use App\Models\Enum\FileStatus;
use App\Models\Enum\FileType;
use App\Models\File;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;
use Orchid\Attachment\Models\Attachment;
use Orchid\Screen\Actions\Button;
use Orchid\Screen\Actions\Link;
use Orchid\Screen\Fields\Input;
use Orchid\Screen\Fields\TextArea;
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
            'files' => File::paginate()
        ];
    }

    /**
     * The name of the screen displayed in the header.
     *
     * @return string|null
     */
    public function name(): ?string
    {
        return 'File Upload';
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
                Upload::make('file')
                    ->title('File')
                    ->storage('local'),
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
                TD::make('status')
                    ->render(function (File $file) {
                        return FileStatus::getKey($file->status);
                    }),
                TD::make('Action')
                    ->render(function (File $file) {
                        return Button::make('Data Import')->icon('upload')
                                ->method('data_import', [
                                    'file_id' => $file->id,
                                ])->class('btn btn-primary my-1') .
                            Button::make('POS')->icon('file-word')
                                ->method('pos', [
                                    'file_id' => $file->id,
                                ])->class('btn btn-warning my-1')->canSee($file->status >= FileStatus::DATA_IMPORTED) .
                            Button::make('SERP & Check')->icon('search')
                                ->method('serp', [
                                    'file_id' => $file->id,
                                ])->class('btn btn-info my-1')->canSee($file->status >= FileStatus::POS_FINISHED) .
                            Link::make('Export')->icon('download')->target('_blank')
                                ->route('platform.files.export', [
                                    'file_id' => $file->id,
                                ])->class('btn btn-success my-1')->canSee($file->status >= FileStatus::SERP_FINISHED) .
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
            $file = File::data($attachment->original_name, suffix: $attachment->extension);
            $file->attachment()->sync([$attachment->id], false);
        }

        Alert::success('File imported successfully!');
    }

    public function data_import(Request $request)
    {
        $file = File::find($request->get('file_id'));
        if ($file) {
            KeywordImportJob::dispatch('file:import', ['--file_id' => $file->id]);
            Alert::warning('File data importing ...');
        }
    }

    public function pos(Request $request)
    {
        $file = File::find($request->get('file_id'));
        if ($file) {
            KeywordPosJob::dispatch($file)->onQueue('pos_queue');
            Alert::warning('POS running ...');
        }
    }

    public function serp(Request $request)
    {
        $file = File::find($request->get('file_id'));
        if ($file) {
            KeywordSerpJob::dispatch($file)->onQueue('serp_queue');
            Alert::warning('SERP running ...');
        }
    }

    public function delete(Request $request)
    {
        $file = File::find($request->get('file_id'));
        $file->delete();
        Alert::success('File was deleted!');
    }
}
