<?php
use avadim\FastExcelWriter\Excel;
define('NO_AGENT_CHECK', true);
define('STOP_STATISTICS', true);
define('PUBLIC_AJAX_MODE', true);
define('NOT_CHECK_PERMISSIONS', true);

// подключение служебной части пролога
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");
    $arResData = [];

    // CSRF: ссылка на экспорт формируется на странице приложения (валидный sessid)
    if(!check_bitrix_sessid()){
        ShowError('Неверный токен сессии');
        require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/epilog_after.php");
        die();
    }

    // строгая валидация имени файла: только <32 hex>.json и физически внутри tmp/
    $fileName = basename(trim((string)($_REQUEST['ds'] ?? '')));
    $tmpDir   = $_SERVER["DOCUMENT_ROOT"].'/employers_calls/tmp';
    $realPath = realpath($tmpDir.'/'.$fileName);
    $realBase = realpath($tmpDir);

    if(!preg_match('/^[a-f0-9]{32}\.json$/', $fileName)
        || $realPath === false || $realBase === false
        || strpos($realPath, $realBase.DIRECTORY_SEPARATOR) !== 0){
        ShowError('Файл не найден');
        require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/epilog_after.php");
        die();
    }

    $arData = json_decode(file_get_contents($realPath), true);

    if(is_array($arData)){

        require_once($_SERVER["DOCUMENT_ROOT"].'/employers_calls/libs/vendor/autoload.php');
 
        $header = [
            'Сотрудник',
            'Номер в Битрикс24',
            'Номер телефона',
            'Тип звонка',
            'Время звонка',
            'Дата вызова',
            'Статус',
            'Лид или контакт',
            'Компания'
        ];

        $headerStyle = [
            'font' => [
                'style' => 'bold'
            ],
            'text-align' => 'center',
            'vertical-align' => 'center',
            'border' => 'thin',
            'height' => 18,
        ];

        $excel = Excel::create(['Звонки за период']);
        $sheet = $excel->sheet();

        $sheet->setColDataStyle('A', ['width' => 25])
        ->setColDataStyle('B', ['width' => 20])
        ->setColDataStyle('C', ['width' => 20])
        ->setColDataStyle('D', ['width' => 13])
        ->setColDataStyle('E', ['width' => 15])
        ->setColDataStyle('F', ['width' => 17])
        ->setColDataStyle('G', ['width' => 25])
        ->setColDataStyle('H', ['width' => 25])
        ->setColDataStyle('I', ['width' => 25]);

        $sheet->writeHeader($header, $headerStyle);

        $rowNum = 1;
        foreach($arData as $arDataItem){
            $rowOptions = [
                'height' => 15,
            ];

            $sheet->writeRow($arDataItem, $rowOptions);
        }

        $newXlsPath = sys_get_temp_dir().'/calls_'.bin2hex(random_bytes(8)).'.xlsx';
        $excel->save($newXlsPath);

        if(file_exists($newXlsPath)){
            header('Content-Description: File Transfer');
            header('Content-Type: application/octet-stream');
            header('Content-Disposition: attachment; filename="' . basename($newXlsPath) . '"');
            header('Expires: 0');
            header('Cache-Control: must-revalidate');
            header('Pragma: public');
            header('Content-Length: ' . filesize($newXlsPath));
            flush(); // Flush system output buffer
            readfile($newXlsPath);
    
            unlink($newXlsPath);
            unlink($realPath);
        }
        else{            
            ShowError('Не удалось сформировать файл выгрузки.');
        }
    }

// подключение служебной части эпилога
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/epilog_after.php");