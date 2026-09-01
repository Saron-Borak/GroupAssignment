<?php

namespace App\Support;

use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Streams a report to the browser as a CSV download.
 */
class CsvExporter
{
    /**
     * @param  array<int, string>  $headings
     * @param  iterable<int, array<int, string|int|float|null>>  $rows
     */
    public function download(string $filename, array $headings, iterable $rows): StreamedResponse
    {
        return response()->stream(
            function () use ($headings, $rows) {
                $handle = fopen('php://output', 'w');

                // Excel only detects UTF-8 in a CSV when this byte-order mark
                // is present; without it Khmer names arrive mangled.
                fwrite($handle, "\xEF\xBB\xBF");

                fputcsv($handle, $headings);

                foreach ($rows as $row) {
                    fputcsv($handle, $row);
                }

                fclose($handle);
            },
            200,
            [
                'Content-Type' => 'text/csv; charset=UTF-8',
                'Content-Disposition' => 'attachment; filename="'.$this->safeName($filename).'"',
                'Cache-Control' => 'no-store, no-cache',
            ],
        );
    }

    public function filename(string ...$parts): string
    {
        $parts[] = now()->format('Y-m-d');

        return $this->safeName(implode('-', array_filter($parts)).'.csv');
    }

    protected function safeName(string $name): string
    {
        return preg_replace('/[^A-Za-z0-9._-]+/', '-', $name) ?? 'export.csv';
    }
}
