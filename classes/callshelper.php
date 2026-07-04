<?php
namespace Mattweb\Callsapp;

class CallsAppHelper{
    public static $arCallTypes = [
		'1' => 'Исходящий',
		'2' => 'Входящий',
	];

    public static function getCallStatus(string $callFailedReason): string
    {
        // Статус
        $callStatus = '';
        switch ($callFailedReason) {
            case 'Success call':
                $callStatus = 'Успешный звонок'; // CALL_FAILED_CODE → 200
            break;
            case 'Skipped call':
                $callStatus = 'Пропущенный звонок'; // CALL_FAILED_CODE → 304
            break;
            case 'Temporarily not available':
                $callStatus = 'Временно недоступен'; // CALL_FAILED_CODE → 480
            break;				
            case 'Decline self':
                $callStatus = 'Вызов отменен'; // CALL_FAILED_CODE → 603-S
            break;
        }

        return $callStatus;
    }

    public static function saveArrayToFile(array $arData, string $pathToSave): int|bool
    {
        $dataToSave = '<? return ';
		$dataToSave .= var_export($arData, true);
		$dataToSave .= ';';

		$res = file_put_contents($pathToSave, $dataToSave);
        return $res;
    }

    /**
     * Сохранение данных отчёта в JSON-файл (без исполняемого PHP — безопасно для последующего чтения).
     */
    public static function saveJsonToFile(array $arData, string $pathToSave): int|bool
    {
        return file_put_contents(
            $pathToSave,
            json_encode($arData, JSON_UNESCAPED_UNICODE),
            LOCK_EX
        );
    }

    public static function createJsonResult(array $arResData, bool $die = true): void
    {
        header('Content-Type: application/json; charset='.LANG_CHARSET);
        echo \Bitrix\Main\Web\Json::encode($arResData);

        if($die) die();
    }



}