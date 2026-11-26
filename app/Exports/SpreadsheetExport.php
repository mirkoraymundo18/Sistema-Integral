<?php

namespace App\Exports;

use Illuminate\Validation\ValidationException;
use RuntimeException;
use XMLWriter;
use ZipArchive;

class SpreadsheetExport
{
    public function write(string $name, array $headers, array $widths, iterable $rows): string
    {
        $sheet = tempnam(sys_get_temp_dir(), 'almacen-sheet-');
        $file = tempnam(sys_get_temp_dir(), 'almacen-xlsx-');
        $zip = new ZipArchive;
        $opened = false;
        $finished = false;

        try {
            if ($sheet === false || $file === false) {
                throw new RuntimeException('No se pudo preparar el archivo de Excel.');
            }

            $xml = new XMLWriter;
            if (! $xml->openUri($sheet)) {
                throw new RuntimeException('No se pudo escribir la hoja de Excel.');
            }

            $xml->startDocument('1.0', 'UTF-8');
            $xml->startElementNs(null, 'worksheet', 'http://schemas.openxmlformats.org/spreadsheetml/2006/main');
            $xml->writeRaw('<sheetViews><sheetView workbookViewId="0"><pane ySplit="1" topLeftCell="A2" activePane="bottomLeft" state="frozen"/></sheetView></sheetViews>');
            $xml->startElement('cols');
            foreach ($widths as $index => $width) {
                $column = (string) ($index + 1);
                $xml->startElement('col');
                $xml->writeAttribute('min', $column);
                $xml->writeAttribute('max', $column);
                $xml->writeAttribute('width', (string) $width);
                $xml->writeAttribute('customWidth', '1');
                $xml->endElement();
            }
            $xml->endElement();
            $xml->startElement('sheetData');
            $this->row($xml, $headers);

            $count = 1;
            foreach ($rows as $row) {
                if (++$count > 1048576) {
                    throw ValidationException::withMessages(['exportacion' => 'Acota la consulta para exportar a Excel.']);
                }

                $this->row($xml, $row);
            }

            $xml->endElement();
            $xml->startElement('autoFilter');
            $xml->writeAttribute('ref', 'A1:'.chr(64 + count($headers)).$count);
            $xml->endElement();
            $xml->endElement();
            $xml->endDocument();
            $xml->flush();
            unset($xml);

            if ($zip->open($file, ZipArchive::OVERWRITE) !== true) {
                throw new RuntimeException('No se pudo crear el archivo de Excel.');
            }

            $opened = true;
            $zip->addFromString('[Content_Types].xml', <<<'XML'
<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">
<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>
<Default Extension="xml" ContentType="application/xml"/>
<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>
<Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>
</Types>
XML);
            $zip->addFromString('_rels/.rels', <<<'XML'
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>
</Relationships>
XML);
            $sheetName = htmlspecialchars($name, ENT_XML1 | ENT_QUOTES, 'UTF-8');
            $zip->addFromString('xl/workbook.xml', <<<XML
<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">
<bookViews><workbookView/></bookViews>
<sheets><sheet name="$sheetName" sheetId="1" r:id="rId1"/></sheets>
</workbook>
XML);
            $zip->addFromString('xl/_rels/workbook.xml.rels', <<<'XML'
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>
</Relationships>
XML);
            if (! $zip->addFile($sheet, 'xl/worksheets/sheet1.xml')) {
                throw new RuntimeException('No se pudo incluir la hoja de Excel.');
            }

            $finished = $zip->close();
            $opened = false;
            if (! $finished) {
                throw new RuntimeException('No se pudo guardar el archivo de Excel.');
            }

            clearstatcache(true, $file);

            return $file;
        } finally {
            unset($xml);
            if ($opened) {
                $zip->close();
            }
            if ($sheet !== false) {
                unlink($sheet);
            }
            if (! $finished && $file !== false) {
                unlink($file);
            }
        }
    }

    private function row(XMLWriter $xml, array $values): void
    {
        $xml->startElement('row');
        foreach ($values as $value) {
            $xml->startElement('c');
            if (is_int($value) || is_float($value)) {
                $xml->writeElement('v', (string) $value);
            } else {
                $xml->writeAttribute('t', 'inlineStr');
                $xml->startElement('is');
                $xml->startElement('t');
                $xml->writeAttribute('xml:space', 'preserve');
                $xml->text(preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F]/', '', (string) $value));
                $xml->endElement();
                $xml->endElement();
            }
            $xml->endElement();
        }
        $xml->endElement();
    }
}
