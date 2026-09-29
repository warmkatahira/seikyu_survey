<?php

namespace App\Support;

use Generator;
use Illuminate\Http\UploadedFile;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * CSV helpers tuned for files that are opened and saved in Japanese Excel.
 *
 * Exports carry a UTF-8 byte order mark, without which Excel reads the file as the
 * system's ANSI code page and shows mojibake. Imports accept either UTF-8 or the
 * Shift_JIS variant Excel writes when a user re-saves as "CSV (comma delimited)".
 */
class Csv
{
    private const BOM = "\xEF\xBB\xBF";

    /**
     * @param  list<string>  $headings
     * @param  iterable<int, list<string|int|null>>  $rows
     */
    public static function download(string $filename, array $headings, iterable $rows): StreamedResponse
    {
        return response()->streamDownload(function () use ($headings, $rows): void {
            $handle = fopen('php://output', 'wb');

            fwrite($handle, self::BOM);
            fputcsv($handle, $headings, escape: '');

            foreach ($rows as $row) {
                fputcsv($handle, $row, escape: '');
            }

            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /**
     * Reads an uploaded CSV into rows keyed by their heading.
     *
     * @return Generator<int, array<string, string>>
     */
    public static function rows(UploadedFile $file): Generator
    {
        $contents = self::toUtf8((string) file_get_contents($file->getRealPath()));

        $handle = fopen('php://temp', 'r+b');
        fwrite($handle, $contents);
        rewind($handle);

        $headings = fgetcsv($handle, escape: '');

        if ($headings === false) {
            fclose($handle);

            return;
        }

        $headings = array_map(trim(...), $headings);
        $lineNumber = 1;

        while (($row = fgetcsv($handle, escape: '')) !== false) {
            $lineNumber++;

            if ($row === [null] || $row === ['']) {
                continue;
            }

            $row = array_pad(array_slice($row, 0, count($headings)), count($headings), '');

            yield $lineNumber => array_map(
                fn (?string $value): string => trim((string) $value),
                array_combine($headings, $row),
            );
        }

        fclose($handle);
    }

    private static function toUtf8(string $contents): string
    {
        if (str_starts_with($contents, self::BOM)) {
            return substr($contents, strlen(self::BOM));
        }

        if (mb_check_encoding($contents, 'UTF-8')) {
            return $contents;
        }

        return mb_convert_encoding($contents, 'UTF-8', 'SJIS-win');
    }
}
