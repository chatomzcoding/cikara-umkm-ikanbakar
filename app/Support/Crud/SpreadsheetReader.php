<?php

namespace App\Support\Crud;

use RuntimeException;
use SimpleXMLElement;
use ZipArchive;

final class SpreadsheetReader
{
    private const NS_MAIN = 'http://schemas.openxmlformats.org/spreadsheetml/2006/main';

    private const NS_RELATIONSHIP = 'http://schemas.openxmlformats.org/officeDocument/2006/relationships';

    /**
     * Membaca sheet pertama (xlsx) atau seluruh isi (csv) menjadi daftar baris.
     *
     * @return list<list<string|null>>
     */
    public function read(string $path, string $extension): array
    {
        return match (strtolower($extension)) {
            'xlsx' => $this->readXlsx($path),
            'csv', 'txt' => $this->readCsv($path),
            default => throw new RuntimeException('Format file tidak didukung. Gunakan .xlsx atau .csv.'),
        };
    }

    /** @return list<list<string|null>> */
    private function readCsv(string $path): array
    {
        $handle = fopen($path, 'r');
        if ($handle === false) {
            throw new RuntimeException('File CSV tidak dapat dibaca.');
        }

        try {
            $firstLine = (string) fgets($handle);
            $delimiter = substr_count($firstLine, ';') > substr_count($firstLine, ',') ? ';' : ',';
            rewind($handle);

            $rows = [];
            while (($row = fgetcsv($handle, null, $delimiter, '"', '')) !== false) {
                $rows[] = array_map(fn ($value) => $value === null ? null : (string) $value, $row);
            }
        } finally {
            fclose($handle);
        }

        if (isset($rows[0][0])) {
            $rows[0][0] = preg_replace('/^\xEF\xBB\xBF/', '', $rows[0][0]);
        }

        return $rows;
    }

    /** @return list<list<string|null>> */
    private function readXlsx(string $path): array
    {
        if (! class_exists(ZipArchive::class)) {
            throw new RuntimeException('Import Excel membutuhkan ekstensi PHP zip.');
        }

        $zip = new ZipArchive;
        if ($zip->open($path) !== true) {
            throw new RuntimeException('File Excel tidak valid atau rusak.');
        }

        try {
            $sharedStrings = $this->sharedStrings($zip);
            $sheet = $zip->getFromName($this->firstSheetPath($zip));
            if ($sheet === false) {
                throw new RuntimeException('Sheet pertama pada file Excel tidak ditemukan.');
            }

            return $this->sheetRows($this->xml($sheet), $sharedStrings);
        } finally {
            $zip->close();
        }
    }

    private function firstSheetPath(ZipArchive $zip): string
    {
        $workbook = $zip->getFromName('xl/workbook.xml');
        $relations = $zip->getFromName('xl/_rels/workbook.xml.rels');

        if ($workbook === false || $relations === false) {
            return 'xl/worksheets/sheet1.xml';
        }

        $sheet = $this->xml($workbook)->children(self::NS_MAIN)->sheets->sheet[0] ?? null;
        // Elemen SimpleXML tanpa anak bernilai false saat dicek boolean, jadi harus dibandingkan dengan null.
        $relationId = $sheet !== null ? (string) $sheet->attributes(self::NS_RELATIONSHIP)['id'] : '';

        foreach ($this->xml($relations)->children() as $relation) {
            if ((string) $relation['Id'] === $relationId) {
                $target = ltrim((string) $relation['Target'], '/');

                return str_starts_with($target, 'xl/') ? $target : 'xl/'.$target;
            }
        }

        return 'xl/worksheets/sheet1.xml';
    }

    /** @return list<string> */
    private function sharedStrings(ZipArchive $zip): array
    {
        $content = $zip->getFromName('xl/sharedStrings.xml');
        if ($content === false) {
            return [];
        }

        $strings = [];
        foreach ($this->xml($content)->children(self::NS_MAIN)->si as $item) {
            $strings[] = $this->richText($item);
        }

        return $strings;
    }

    /**
     * @param  list<string>  $sharedStrings
     * @return list<list<string|null>>
     */
    private function sheetRows(SimpleXMLElement $sheet, array $sharedStrings): array
    {
        $rows = [];
        $sheetData = $sheet->children(self::NS_MAIN)->sheetData;

        foreach ($sheetData->row ?? [] as $row) {
            $values = [];
            $position = 0;

            foreach ($row->children(self::NS_MAIN)->c as $cell) {
                $reference = (string) $cell->attributes()['r'];
                $index = $reference !== '' ? $this->columnIndex($reference) : $position;
                $values[$index] = $this->cellValue($cell, $sharedStrings);
                $position = $index + 1;
            }

            $rows[] = $values === [] ? [] : array_replace(array_fill(0, max(array_keys($values)) + 1, null), $values);
        }

        return $rows;
    }

    /** @param list<string> $sharedStrings */
    private function cellValue(SimpleXMLElement $cell, array $sharedStrings): ?string
    {
        $children = $cell->children(self::NS_MAIN);
        // Atribut sel tidak ber-namespace, sedangkan $cell berasal dari children(NS_MAIN).
        $type = (string) $cell->attributes()['t'];

        return match ($type) {
            's' => $sharedStrings[(int) $children->v] ?? null,
            'inlineStr' => $this->richText($children->is),
            'b' => (string) $children->v === '1' ? '1' : '0',
            default => isset($children->v) ? (string) $children->v : null,
        };
    }

    private function richText(SimpleXMLElement $item): string
    {
        $children = $item->children(self::NS_MAIN);
        if (isset($children->t)) {
            return (string) $children->t;
        }

        $text = '';
        foreach ($children->r as $run) {
            $text .= (string) $run->children(self::NS_MAIN)->t;
        }

        return $text;
    }

    private function columnIndex(string $reference): int
    {
        $letters = preg_replace('/\d+/', '', strtoupper($reference));
        $index = 0;

        foreach (str_split($letters) as $letter) {
            $index = $index * 26 + (ord($letter) - 64);
        }

        return $index - 1;
    }

    private function xml(string $content): SimpleXMLElement
    {
        $xml = simplexml_load_string($content, SimpleXMLElement::class, LIBXML_NONET);
        if ($xml === false) {
            throw new RuntimeException('Isi file Excel tidak dapat dibaca.');
        }

        return $xml;
    }
}
