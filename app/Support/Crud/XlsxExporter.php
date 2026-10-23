<?php

namespace App\Support\Crud;

use BackedEnum;
use Closure;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use RuntimeException;
use Symfony\Component\HttpFoundation\StreamedResponse;
use XMLWriter;
use ZipArchive;

final class XlsxExporter
{
    /**
     * @param  array<string, string|Closure(Model): mixed>  $columns
     */
    public function download(Builder $query, array $columns, string $filename): StreamedResponse
    {
        return response()->streamDownload(function () use ($query, $columns): void {
            $path = $this->createTemporaryFile($query, $columns);

            try {
                readfile($path);
            } finally {
                @unlink($path);
            }
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Cache-Control' => 'no-store, no-cache',
        ]);
    }

    /**
     * Pemanggil bertanggung jawab menghapus file sementara yang dikembalikan.
     *
     * @param  array<string, string|Closure(Model): mixed>  $columns
     */
    public function createTemporaryFile(Builder $query, array $columns): string
    {
        if (! class_exists(ZipArchive::class) || ! class_exists(XMLWriter::class)) {
            throw new RuntimeException('Export Excel membutuhkan ekstensi PHP zip dan xmlwriter.');
        }

        $worksheetPath = tempnam(sys_get_temp_dir(), 'crud-sheet-');
        $xlsxPath = tempnam(sys_get_temp_dir(), 'crud-xlsx-');

        if ($worksheetPath === false || $xlsxPath === false) {
            throw new RuntimeException('Tidak dapat membuat file sementara untuk export Excel.');
        }

        try {
            $this->writeWorksheet($worksheetPath, $query, $columns);
            $this->writeArchive($xlsxPath, $worksheetPath);
        } catch (\Throwable $exception) {
            @unlink($xlsxPath);
            throw $exception;
        } finally {
            @unlink($worksheetPath);
        }

        return $xlsxPath;
    }

    /** @param array<string, string|Closure(Model): mixed> $columns */
    private function writeWorksheet(string $path, Builder $query, array $columns): void
    {
        $xml = new XMLWriter;
        if (! $xml->openUri($path)) {
            throw new RuntimeException('Tidak dapat menulis worksheet Excel.');
        }
        $xml->startDocument('1.0', 'UTF-8');
        $xml->startElement('worksheet');
        $xml->writeAttribute('xmlns', 'http://schemas.openxmlformats.org/spreadsheetml/2006/main');
        $xml->startElement('sheetViews');
        $xml->startElement('sheetView');
        $xml->writeAttribute('workbookViewId', '0');
        $xml->startElement('pane');
        $xml->writeAttribute('ySplit', '1');
        $xml->writeAttribute('topLeftCell', 'A2');
        $xml->writeAttribute('activePane', 'bottomLeft');
        $xml->writeAttribute('state', 'frozen');
        $xml->endElement();
        $xml->endElement();
        $xml->endElement();
        $this->writeColumnWidths($xml, array_keys($columns));
        $xml->startElement('sheetData');

        $this->writeRow($xml, 1, array_keys($columns), true);
        $rowNumber = 2;

        $query->chunkById(500, function ($records) use (&$rowNumber, $columns, $xml): void {
            foreach ($records as $record) {
                $values = [];
                foreach ($columns as $resolver) {
                    $values[] = $this->resolveValue($record, $resolver);
                }
                $this->writeRow($xml, $rowNumber++, $values);
            }
        });

        $xml->endElement();
        if ($rowNumber > 2 && $columns !== []) {
            $xml->startElement('autoFilter');
            $xml->writeAttribute('ref', 'A1:'.$this->columnLetter(count($columns)).($rowNumber - 1));
            $xml->endElement();
        }
        $xml->endElement();
        $xml->endDocument();
        $xml->flush();
    }

    /** @param array<int, mixed> $values */
    private function writeRow(XMLWriter $xml, int $rowNumber, array $values, bool $header = false): void
    {
        $xml->startElement('row');
        $xml->writeAttribute('r', (string) $rowNumber);

        foreach ($values as $index => $value) {
            $xml->startElement('c');
            $xml->writeAttribute('r', $this->columnLetter($index + 1).$rowNumber);
            if ($header) {
                $xml->writeAttribute('s', '1');
            }

            if (! $header && (is_int($value) || is_float($value))) {
                $xml->writeAttribute('t', 'n');
                $xml->writeElement('v', (string) $value);
            } else {
                $xml->writeAttribute('t', 'inlineStr');
                $xml->startElement('is');
                $xml->startElement('t');
                $xml->writeAttribute('xml:space', 'preserve');
                $xml->text((string) $value);
                $xml->endElement();
                $xml->endElement();
            }
            $xml->endElement();
        }

        $xml->endElement();
    }

    /** @param array<int, string> $headings */
    private function writeColumnWidths(XMLWriter $xml, array $headings): void
    {
        if ($headings === []) {
            return;
        }

        $xml->startElement('cols');
        foreach ($headings as $index => $heading) {
            $xml->startElement('col');
            $xml->writeAttribute('min', (string) ($index + 1));
            $xml->writeAttribute('max', (string) ($index + 1));
            $xml->writeAttribute('width', (string) min(40, max(12, mb_strlen($heading) + 4)));
            $xml->writeAttribute('customWidth', '1');
            $xml->endElement();
        }
        $xml->endElement();
    }

    private function resolveValue(Model $record, string|Closure $resolver): mixed
    {
        $value = $resolver instanceof Closure ? $resolver($record) : data_get($record, $resolver);

        if ($value instanceof BackedEnum) {
            $value = $value->value;
        }
        if ($value instanceof DateTimeInterface) {
            return $value->format('Y-m-d H:i:s');
        }
        if (is_bool($value)) {
            return $value ? 'Ya' : 'Tidak';
        }
        if (is_array($value) || is_object($value)) {
            return json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }

        return $value ?? '';
    }

    private function writeArchive(string $path, string $worksheetPath): void
    {
        $zip = new ZipArchive;
        if ($zip->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new RuntimeException('Tidak dapat membuat arsip XLSX.');
        }

        $zip->addFromString('[Content_Types].xml', $this->contentTypesXml());
        $zip->addFromString('_rels/.rels', $this->rootRelationshipsXml());
        $zip->addFromString('xl/workbook.xml', $this->workbookXml());
        $zip->addFromString('xl/_rels/workbook.xml.rels', $this->workbookRelationshipsXml());
        $zip->addFromString('xl/styles.xml', $this->stylesXml());
        $zip->addFile($worksheetPath, 'xl/worksheets/sheet1.xml');
        $zip->setArchiveComment('Generated by Linqo CRUD');
        $zip->close();
    }

    private function columnLetter(int $number): string
    {
        $letters = '';
        while ($number > 0) {
            $number--;
            $letters = chr(65 + ($number % 26)).$letters;
            $number = intdiv($number, 26);
        }

        return $letters;
    }

    private function contentTypesXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"><Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/><Default Extension="xml" ContentType="application/xml"/><Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/><Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/><Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/></Types>';
    }

    private function rootRelationshipsXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/></Relationships>';
    }

    private function workbookXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"><sheets><sheet name="Data" sheetId="1" r:id="rId1"/></sheets></workbook>';
    }

    private function workbookRelationshipsXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/><Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/></Relationships>';
    }

    private function stylesXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><fonts count="2"><font><sz val="11"/><name val="Calibri"/></font><font><b/><color rgb="FFFFFFFF"/><sz val="11"/><name val="Calibri"/></font></fonts><fills count="3"><fill><patternFill patternType="none"/></fill><fill><patternFill patternType="gray125"/></fill><fill><patternFill patternType="solid"><fgColor rgb="FF2563EB"/><bgColor indexed="64"/></patternFill></fill></fills><borders count="1"><border><left/><right/><top/><bottom/><diagonal/></border></borders><cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs><cellXfs count="2"><xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/><xf numFmtId="0" fontId="1" fillId="2" borderId="0" xfId="0" applyFont="1" applyFill="1"/></cellXfs></styleSheet>';
    }
}
