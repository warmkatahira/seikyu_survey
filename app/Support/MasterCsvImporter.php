<?php

namespace App\Support;

use App\Enums\MasterType;
use App\Models\Office;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

/**
 * Imports a master CSV as all-or-nothing.
 *
 * Every row is checked before anything is written, so a file with a typo in the middle
 * leaves the master untouched and the administrator gets the full list of problems in
 * one pass instead of a half-applied import.
 */
class MasterCsvImporter
{
    /**
     * @return array{created: int, updated: int, errors: list<string>}
     */
    public function import(MasterType $type, UploadedFile $file): array
    {
        $headings = $type->headings();
        $officeIds = Office::query()->pluck('id', 'code');

        $errors = [];
        $records = [];
        $seenCodes = [];

        foreach (Csv::rows($file) as $line => $row) {
            if (! array_key_exists($type->codeHeading(), $row)) {
                return [
                    'created' => 0,
                    'updated' => 0,
                    'errors' => ['見出し行が一致しません。1行目を「'.implode(',', $headings).'」にしてください。'],
                ];
            }

            $code = $row[$type->codeHeading()];
            $name = $row[$type->nameHeading()] ?? '';

            if ($code === '' && $name === '') {
                continue;
            }

            if ($code === '') {
                $errors[] = "{$line}行目: {$type->codeHeading()}が空です。";

                continue;
            }

            if (isset($seenCodes[$code])) {
                $errors[] = "{$line}行目: {$type->codeHeading()}「{$code}」がファイル内で重複しています（{$seenCodes[$code]}行目と同じ）。";

                continue;
            }

            $seenCodes[$code] = $line;

            if ($name === '') {
                $errors[] = "{$line}行目: {$type->nameHeading()}が空です。";

                continue;
            }

            $attributes = [
                'name' => $name,
                'sort_order' => $this->toSortOrder($row['表示順'] ?? ''),
                'is_active' => $this->toBool($row['有効'] ?? ''),
            ];

            if ($type->hasOffice()) {
                $officeCode = $row['所属営業所コード'] ?? '';

                if ($officeCode !== '' && ! $officeIds->has($officeCode)) {
                    $errors[] = "{$line}行目: 所属営業所コード「{$officeCode}」が営業所マスタにありません。";

                    continue;
                }

                $attributes['office_id'] = $officeCode === '' ? null : $officeIds->get($officeCode);
            }

            $records[$code] = $attributes;
        }

        if ($errors !== []) {
            return ['created' => 0, 'updated' => 0, 'errors' => $errors];
        }

        return DB::transaction(function () use ($type, $records): array {
            $created = 0;
            $updated = 0;

            foreach ($records as $code => $attributes) {
                $model = $type->modelClass()::updateOrCreate(['code' => $code], $attributes);

                $model->wasRecentlyCreated ? $created++ : $updated++;
            }

            return ['created' => $created, 'updated' => $updated, 'errors' => []];
        });
    }

    private function toSortOrder(string $value): int
    {
        return is_numeric($value) ? max(0, (int) $value) : 0;
    }

    /**
     * Blank means active: a freshly typed row should not silently be hidden from the form.
     */
    private function toBool(string $value): bool
    {
        return ! in_array(mb_strtolower($value), ['0', '無効', 'false', 'いいえ', 'no', 'n'], true);
    }
}
