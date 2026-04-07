<?php

namespace App\Support;

use App\Models\Lead;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class LeadExportBuilder
{
    /**
     * @param array<string, string> $stageOptions
     * @param array<string, string> $sourceOptions
     * @param array<string, string> $procedureOptions
     */
    public function __construct(
        private readonly array $stageOptions,
        private readonly array $sourceOptions,
        private readonly array $procedureOptions
    ) {
    }

    public function toExcel(Collection $leads, array $context): string
    {
        $rows = $this->mapRows($leads);

        $sheetRows = [
            ['style' => 'title', 'cells' => [$context['title'] ?? 'CRM Leads Export']],
            ['style' => 'meta', 'cells' => ['Scope', $context['scope_label'] ?? 'All Leads']],
            ['style' => 'meta', 'cells' => ['Generated At', $context['generated_at'] ?? $this->formatDateTime(now())]],
            ['style' => 'meta', 'cells' => ['Filters', $context['filter_summary'] ?? 'No filters applied']],
            ['style' => 'meta', 'cells' => ['Total Leads', (string) $rows->count()]],
            ['style' => null, 'cells' => []],
            ['style' => 'header', 'cells' => ['Name', 'Phone', 'Source', 'Created At', 'Procedure of Interest', 'Stage', 'Status', 'Next Follow-up', 'Assigned User']],
        ];

        foreach ($rows as $row) {
            $sheetRows[] = [
                'style' => null,
                'cells' => [
                    $row['name'],
                    $row['phone'],
                    $row['source'],
                    $row['created_at'],
                    $row['procedures'],
                    $row['stage'],
                    $row['status'],
                    $row['next_follow_up_at'],
                    $row['assigned_to'],
                ],
            ];
        }

        $xml = [
            '<?xml version="1.0" encoding="UTF-8"?>',
            '<?mso-application progid="Excel.Sheet"?>',
            '<Workbook xmlns="urn:schemas-microsoft-com:office:spreadsheet" xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns:ss="urn:schemas-microsoft-com:office:spreadsheet" xmlns:html="http://www.w3.org/TR/REC-html40">',
            '<Styles>',
            '<Style ss:ID="Default" ss:Name="Normal"><Alignment ss:Vertical="Top" ss:WrapText="1" /></Style>',
            '<Style ss:ID="title"><Font ss:Bold="1" ss:Size="14" /></Style>',
            '<Style ss:ID="meta"><Font ss:Bold="1" /></Style>',
            '<Style ss:ID="header"><Font ss:Bold="1" /><Interior ss:Color="#F2F4F7" ss:Pattern="Solid" /></Style>',
            '</Styles>',
            '<Worksheet ss:Name="Leads">',
            '<Table>',
        ];

        foreach ($sheetRows as $row) {
            $styleAttribute = $row['style'] !== null ? ' ss:StyleID="'.$row['style'].'"' : '';
            $xml[] = '<Row'.$styleAttribute.'>';

            foreach ($row['cells'] as $cell) {
                $xml[] = '<Cell><Data ss:Type="String">'.$this->escapeXml((string) $cell).'</Data></Cell>';
            }

            $xml[] = '</Row>';
        }

        $xml[] = '</Table>';
        $xml[] = '</Worksheet>';
        $xml[] = '</Workbook>';

        return implode('', $xml);
    }

    public function toPdf(Collection $leads, array $context): string
    {
        $rows = $this->mapRows($leads);
        $headerLines = $this->pdfHeaderLines($context, $rows->count());
        $pages = [];
        $pageLines = $headerLines;
        $maxLinesPerPage = 42;

        foreach ($rows as $row) {
            $block = [
                $this->formatPdfColumns([
                    [$row['name'], 18],
                    [$row['phone'], 14],
                    [$row['source'], 12],
                    [$row['created_at'], 18],
                    [$row['stage'], 14],
                    [$row['status'], 8],
                    [$row['next_follow_up_at'], 18],
                ]),
                $this->truncate('Procedures: '.$row['procedures'], 94).'  '.$this->truncate('User: '.$row['assigned_to'], 24),
                str_repeat('-', 122),
            ];

            if (count($pageLines) + count($block) > $maxLinesPerPage) {
                $pages[] = $pageLines;
                $pageLines = $headerLines;
            }

            $pageLines = array_merge($pageLines, $block);
        }

        if ($rows->isEmpty()) {
            $pageLines[] = 'No leads matched the selected export criteria.';
        }

        $pages[] = $pageLines;

        return $this->renderPdf($pages);
    }

    private function pdfHeaderLines(array $context, int $total): array
    {
        $headerLines = [
            $this->truncate($context['title'] ?? 'CRM Leads Export', 122),
            $this->truncate('Scope: '.($context['scope_label'] ?? 'All Leads'), 122),
            $this->truncate('Generated: '.($context['generated_at'] ?? $this->formatDateTime(now())), 122),
        ];

        $filterSummary = 'Filters: '.($context['filter_summary'] ?? 'No filters applied');
        foreach ($this->wrapText($filterSummary, 122) as $line) {
            $headerLines[] = $line;
        }

        $headerLines[] = 'Total Leads: '.$total;
        $headerLines[] = '';
        $headerLines[] = $this->formatPdfColumns([
            ['Name', 18],
            ['Phone', 14],
            ['Source', 12],
            ['Created At', 18],
            ['Stage', 14],
            ['Status', 8],
            ['Next Follow-up', 18],
        ]);
        $headerLines[] = str_repeat('-', 122);

        return $headerLines;
    }

    private function renderPdf(array $pages): string
    {
        $objects = [
            1 => '<< /Type /Catalog /Pages 2 0 R >>',
            2 => '<< /Type /Pages /Count '.count($pages).' /Kids [__KIDS__] >>',
            3 => '<< /Type /Font /Subtype /Type1 /BaseFont /Courier >>',
        ];

        $pageObjectNumbers = [];
        $nextObjectNumber = 4;

        foreach ($pages as $pageLines) {
            $contentObjectNumber = $nextObjectNumber++;
            $pageObjectNumber = $nextObjectNumber++;

            $stream = $this->pdfContentStream($pageLines);
            $objects[$contentObjectNumber] = '<< /Length '.strlen($stream).' >>'."\n".'stream'."\n".$stream."\n".'endstream';
            $objects[$pageObjectNumber] = '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 842 595] /Resources << /Font << /F1 3 0 R >> >> /Contents '.$contentObjectNumber.' 0 R >>';
            $pageObjectNumbers[] = $pageObjectNumber;
        }

        $objects[2] = str_replace(
            '__KIDS__',
            implode(' ', array_map(static fn (int $pageNumber): string => $pageNumber.' 0 R', $pageObjectNumbers)),
            $objects[2]
        );

        ksort($objects);

        $pdf = "%PDF-1.4\n";
        $offsets = [0];
        $objectCount = count($objects);

        for ($index = 1; $index <= $objectCount; $index++) {
            $offsets[$index] = strlen($pdf);
            $pdf .= $index." 0 obj\n".$objects[$index]."\nendobj\n";
        }

        $xrefPosition = strlen($pdf);
        $pdf .= "xref\n0 ".($objectCount + 1)."\n";
        $pdf .= "0000000000 65535 f \n";

        for ($index = 1; $index <= $objectCount; $index++) {
            $pdf .= sprintf('%010d 00000 n ', $offsets[$index])."\n";
        }

        $pdf .= "trailer\n<< /Size ".($objectCount + 1)." /Root 1 0 R >>\n";
        $pdf .= "startxref\n".$xrefPosition."\n%%EOF";

        return $pdf;
    }

    private function pdfContentStream(array $lines): string
    {
        $stream = "BT\n/F1 9 Tf\n11 TL\n28 560 Td\n";

        foreach (array_values($lines) as $index => $line) {
            if ($index > 0) {
                $stream .= "T*\n";
            }

            $stream .= '('.$this->escapePdfText($line).") Tj\n";
        }

        $stream .= "ET";

        return $stream;
    }

    private function escapePdfText(string $value): string
    {
        $sanitized = preg_replace('/\s+/', ' ', trim($value)) ?? '';
        $converted = @iconv('UTF-8', 'Windows-1252//TRANSLIT//IGNORE', $sanitized);

        if ($converted === false) {
            $converted = $sanitized;
        }

        return strtr($converted, [
            '\\' => '\\\\',
            '(' => '\(',
            ')' => '\)',
        ]);
    }

    private function formatPdfColumns(array $columns): string
    {
        return implode(' | ', array_map(
            fn (array $column): string => str_pad(
                $this->truncate((string) $column[0], (int) $column[1]),
                (int) $column[1]
            ),
            $columns
        ));
    }

    private function wrapText(string $text, int $width): array
    {
        return explode("\n", wordwrap($this->normalizeText($text), $width, "\n", true));
    }

    private function truncate(string $value, int $width): string
    {
        $normalized = $this->normalizeText($value);

        if (mb_strlen($normalized) <= $width) {
            return $normalized;
        }

        if ($width <= 3) {
            return mb_substr($normalized, 0, $width);
        }

        return mb_substr($normalized, 0, $width - 3).'...';
    }

    private function normalizeText(string $value): string
    {
        return trim((string) preg_replace('/\s+/', ' ', $value));
    }

    private function escapeXml(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_XML1, 'UTF-8');
    }

    private function mapRows(Collection $leads): Collection
    {
        return $leads->map(function (Lead $lead): array {
            return [
                'name' => trim((string) ($lead->contact?->full_name ?? 'Unnamed Lead')) ?: 'Unnamed Lead',
                'phone' => trim((string) ($lead->contact?->phone ?? '')) ?: '-',
                'source' => $this->sourceOptions[(string) $lead->source_platform] ?? ucfirst(str_replace('_', ' ', (string) $lead->source_platform)),
                'created_at' => $this->formatDateTime($lead->created_at),
                'procedures' => $this->procedureLabelString($lead),
                'stage' => $this->stageOptions[$this->normalizeStage((string) $lead->stage)] ?? ucfirst(str_replace('_', ' ', (string) $lead->stage)),
                'status' => ucfirst((string) $lead->status),
                'next_follow_up_at' => $this->formatDateTime($lead->next_follow_up_at),
                'assigned_to' => trim((string) ($lead->assignedTo?->name ?? '')) ?: 'Unassigned',
            ];
        });
    }

    private function procedureLabelString(Lead $lead): string
    {
        $procedureKeys = collect(data_get($lead->meta, 'procedures_of_interest', []))
            ->map(static fn ($value): string => (string) $value)
            ->filter(static fn (string $value): bool => $value !== '')
            ->unique()
            ->values();

        $labels = $procedureKeys
            ->map(function (string $procedureKey) use ($lead): string {
                if ($procedureKey === 'other') {
                    $otherValue = trim((string) data_get($lead->meta, 'procedure_other', ''));

                    return $otherValue !== '' ? 'Other: '.$otherValue : 'Other';
                }

                return $this->procedureOptions[$procedureKey] ?? ucfirst(str_replace('_', ' ', $procedureKey));
            })
            ->filter(static fn (string $value): bool => $value !== '')
            ->values()
            ->all();

        return !empty($labels) ? implode(', ', $labels) : 'Not selected';
    }

    private function normalizeStage(string $stage): string
    {
        return match ($stage) {
            'initial' => 'new',
            'proposal' => 'negotiation',
            'confirmed' => 'booked',
            default => $stage,
        };
    }

    private function formatDateTime(mixed $value): string
    {
        if ($value === null || $value === '') {
            return '-';
        }

        $date = $value instanceof Carbon ? $value : Carbon::parse((string) $value);

        return $date->timezone('Asia/Karachi')->format('d M Y h:i A').' PKT';
    }
}
