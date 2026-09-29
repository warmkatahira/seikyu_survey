<?php

namespace App\Http\Controllers\Admin;

use App\Enums\MasterType;
use App\Http\Controllers\Controller;
use App\Support\Csv;
use App\Support\MasterCsvImporter;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class MasterCsvController extends Controller
{
    public function show(MasterType $master): StreamedResponse
    {
        return Csv::download(
            $master->csvFilename(),
            $master->headings(),
            $master->query()->cursor()->map(fn (Model $model): array => $master->toRow($model)),
        );
    }

    public function store(Request $request, MasterType $master, MasterCsvImporter $importer): RedirectResponse
    {
        $request->validate(
            ['file' => ['required', 'file', 'mimetypes:text/plain,text/csv,application/csv,application/vnd.ms-excel', 'max:5120']],
            attributes: ['file' => 'CSVファイル'],
        );

        $result = $importer->import($master, $request->file('file'));

        if ($result['errors'] !== []) {
            return back()->with('importErrors', $result['errors']);
        }

        return back()->with(
            'status',
            "{$master->label()}を取り込みました。新規 {$result['created']} 件 / 更新 {$result['updated']} 件",
        );
    }
}
