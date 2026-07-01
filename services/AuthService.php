<?php
class AuthService
{
    private $excelFile;

    public function __construct($excelFile)
    {
        if (!file_exists($excelFile)) {
            throw new Exception("فایل اکسل یافت نشد: $excelFile");
        }
        $this->excelFile = $excelFile;
    }

    /**
     * احراز هویت کاربر بر اساس کد ملی و شماره شناسنامه
     */
    public function authenticate($nationalCode, $idNumber)
    {
        $rows = $this->readExcel();

        foreach ($rows as $row) {
            if (
                isset($row['کد ملی'], $row['شماره شناسنامه']) &&
                $row['کد ملی'] === $nationalCode &&
                $row['شماره شناسنامه'] === $idNumber
            ) {
                return $row; // کاربر پیدا شد
            }

            // پشتیبانی از ستون‌های انگلیسی اگر فایل Excel انگلیسی باشد
            if (
                isset($row['NationalCode'], $row['IdNumber']) &&
                $row['NationalCode'] === $nationalCode &&
                $row['IdNumber'] === $idNumber
            ) {
                return $row;
            }
        }

        return null; // کاربر پیدا نشد
    }

    /**
     * خواندن فایل Excel (.xlsx) با PHP خالص
     */
    private function readExcel()
    {
        $zip = new ZipArchive;
        if ($zip->open($this->excelFile) === TRUE) {
            // خواندن محتوای sheet اصلی
            $xml = $zip->getFromName('xl/worksheets/sheet1.xml');
            $sharedStringsXml = $zip->getFromName('xl/sharedStrings.xml');
            $zip->close();
        } else {
            throw new Exception("امکان باز کردن فایل اکسل وجود ندارد.");
        }

        // پردازش sharedStrings
        $sharedStrings = [];
        if ($sharedStringsXml) {
            $sxml = simplexml_load_string($sharedStringsXml);
            foreach ($sxml->si as $si) {
                $sharedStrings[] = (string)$si->t;
            }
        }

        // پردازش sheet و تبدیل به آرایه
        $sxml = simplexml_load_string($xml);
        $rows = [];
        $headers = [];

        foreach ($sxml->sheetData->row as $i => $row) {
            $rowData = [];
            foreach ($row->c as $c) {
                $value = (string)$c->v;
                if ((string)$c['t'] === 's') {
                    $value = $sharedStrings[intval($value)];
                }

                $columnIndex = $this->columnIndexFromName((string)$c['r']);
                $rowData[$columnIndex] = $value;
            }

            if ($i == 0) {
                // ردیف اول => header
                $headers = $rowData;
            } else {
                $rows[] = array_combine($headers, $rowData);
            }
        }

        return $rows;
    }

    /**
     * تبدیل نام سلول مثل A1 به شماره ستون (0-based)
     */
    private function columnIndexFromName($cellRef)
    {
        preg_match('/([A-Z]+)(\d+)/', $cellRef, $matches);
        $col = $matches[1];
        $index = 0;
        $len = strlen($col);
        for ($i = 0; $i < $len; $i++) {
            $index *= 26;
            $index += ord($col[$i]) - ord('A') + 1;
        }
        return $index - 1;
    }
}
