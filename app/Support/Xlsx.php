<?php

namespace App\Support;

use OpenSpout\Common\Entity\Row;
use OpenSpout\Common\Entity\Style\Style;
use OpenSpout\Writer\AutoFilter;
use OpenSpout\Writer\XLSX\Entity\SheetView;
use OpenSpout\Writer\XLSX\Options;
use OpenSpout\Writer\XLSX\Writer;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Excel (.xlsx) downloads laid out ready to read: a bold, shaded heading row that stays
 * put while scrolling, a filter on every column, and columns sized to their contents.
 */
class Xlsx
{
    private const FONT = '游ゴシック';

    private const FONT_SIZE = 11;

    /** Column widths are measured in characters; Japanese counts double. */
    private const MIN_WIDTH = 8;

    private const MAX_WIDTH = 50;

    /**
     * @param  list<string>  $headings
     * @param  iterable<int, list<string|int|null>>  $rows
     */
    public static function download(string $filename, string $sheetName, array $headings, iterable $rows): StreamedResponse
    {
        // Buffered because column widths must be known before the first row is written.
        // Survey answers number in the hundreds, so this stays small.
        $rows = is_array($rows) ? $rows : iterator_to_array($rows, false);

        return response()->streamDownload(function () use ($sheetName, $headings, $rows): void {
            $writer = new Writer(new Options(new Style(fontSize: self::FONT_SIZE, fontName: self::FONT)));
            $writer->openToFile('php://output');

            $sheet = $writer->getCurrentSheet();
            $sheet->setName($sheetName);
            $sheet->setSheetView((new SheetView)->withFreezeRow(2));
            $sheet->setAutoFilter(new AutoFilter(0, 1, count($headings) - 1, count($rows) + 1));

            foreach (self::columnWidths($headings, $rows) as $column => $width) {
                $sheet->setColumnWidth($width, $column + 1);
            }

            $writer->addRow(Row::fromValuesWithStyle($headings, new Style(
                fontBold: true,
                fontSize: self::FONT_SIZE,
                fontName: self::FONT,
                backgroundColor: 'E2E8F0',
            )));

            foreach ($rows as $row) {
                $writer->addRow(Row::fromValues($row));
            }

            $writer->close();
        }, $filename, ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']);
    }

    /**
     * @param  list<string>  $headings
     * @param  list<list<string|int|null>>  $rows
     * @return list<int>
     */
    private static function columnWidths(array $headings, array $rows): array
    {
        return array_map(function (int $column) use ($headings, $rows): int {
            // Room for the filter button beside the heading.
            $widest = mb_strwidth($headings[$column]) + 4;

            foreach ($rows as $row) {
                $widest = max($widest, mb_strwidth((string) ($row[$column] ?? '')) + 2);
            }

            return min(max($widest, self::MIN_WIDTH), self::MAX_WIDTH);
        }, array_keys($headings));
    }
}
