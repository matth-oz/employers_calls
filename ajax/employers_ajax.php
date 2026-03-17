<?php
use Mattweb\RestB24;
use Mattweb\Callsapp;

$EMPLOYERS_LIST_PATH = $_SERVER['DOCUMENT_ROOT'].'/calls_gkcit_app/data/employers_list.php';

require_once($_SERVER["DOCUMENT_ROOT"]."/calls_gkcit_app/data/settings.php");
require_once($_SERVER["DOCUMENT_ROOT"]."/calls_gkcit_app/classes/rest_b24_department.php");
require_once($_SERVER["DOCUMENT_ROOT"]."/calls_gkcit_app/classes/departmenthelper.php");
require_once($_SERVER["DOCUMENT_ROOT"]."/calls_gkcit_app/classes/callshelper.php");

define('NO_AGENT_CHECK', true);
define('STOP_STATISTICS', true);
define('PUBLIC_AJAX_MODE', true);
define('NOT_CHECK_PERMISSIONS', true);


/*
	params:
	portal_user_id - идентификатор сотрудника
	calls_date_from - начальная дата диапазона
	calls_date_to - конечная дата диапазона

	act => listEmps
	act => addRemEmp
	act => clearEmps
*/

// подключение служебной части пролога
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");

// Callsapp\CallsAppHelper::createJsonResult($_POST);

if(isset($_POST['ajax'])){
	if(isset($_POST['act'])){
		$arResData = $employersList = [];

		$bxDptAPI = new RestB24\RestBx24Department(C_REST_WEB_HOOK_URL);

		$action = trim($_POST['act']);

		switch($action){
			case 'listEmps': // получаем список сотрудников раздела (отмечаем выбранных сотрудников)
				if(intval($_POST['depId']) > 0){
					$depId = intval($_POST['depId']);
					$result = $bxDptAPI->callMethod(
						'user.get', 
						[
							"UF_DEPARTMENT" => $depId,
							"ACTIVE" => "Y",
							"SORT" => "ID",
							"ORDER" => "asc",
						]);

					if(!empty($result['result'])){
						$arResult = [];

						if(file_exists($EMPLOYERS_LIST_PATH)){
							$employersList = require($EMPLOYERS_LIST_PATH);
						}

						foreach($result['result'] as $arEmployer){
							$arResult[$arEmployer['ID']] = $arEmployer;

							if(is_array($employersList) && array_key_exists($arEmployer['ID'], $employersList)){
								$arResult[$arEmployer['ID']]['CHECKED'] = 'Y';
							}
						}

						$arResData['STATUS'] = 'success';
						$arResData['RESULT'] = $arResult;			
					}
					else{
						$arResData['STATUS'] = 'error';
						$arResData['MESS'][] = 'Список сотрудников пуст!';
					}
				}

			break;
			case 'addRemEmp': // добавляем/удаляем сотрудников
				if(!empty(trim($_POST['addEmpStr'])) || !empty(trim($_POST['remEmpStr']))) {
					$arEmployers = [];

					if(file_exists($EMPLOYERS_LIST_PATH)){
						$employersList = require($EMPLOYERS_LIST_PATH);
					}

					// добавляем сотрудников
					if(!empty(trim($_POST['addEmpStr']))){
						$employersStr = trim($_POST['addEmpStr'], '||');

						$arEmployersTmp = explode('||', $employersStr);
						foreach($arEmployersTmp as $employerStr){
							$arEmployer = explode('|', $employerStr);

							$arEmployers[$arEmployer[0]] = [
								'ID'=> $arEmployer[0],
								'NAME'=> $arEmployer[1],
							];
						}

						if(is_array($employersList) && count($employersList) > 0){
							$arEmployers = $arEmployers + $employersList;
						}

					}

					// удаляем сотрудников
					if(!empty(trim($_POST['remEmpStr']))){
						$remEmployersStr = trim($_POST['remEmpStr'], '|');

						if(mb_strpos($remEmployersStr,'|') !== false){
							$arEmployersRemove = explode('|', $remEmployersStr);
						}
						else{
							$arEmployersRemove[0] = $remEmployersStr;	
						}
						
						foreach($arEmployersRemove as $employerIdRemove){
							unset($employersList[$employerIdRemove]);
						}

						$arEmployers = $employersList;
					}					

					Callsapp\CallsAppHelper::saveArrayToFile($arEmployers, $EMPLOYERS_LIST_PATH);

					$arResData['STATUS'] = 'success';
					$arCurrentChecked = array_keys($arEmployers);
					$arResData['RESULT'] = CUtil::PhpToJSObject($arCurrentChecked);					
				}
			break;
			case 'clearEmps':
				if(!empty(trim($_POST['empStr']))) {
					$arEmployers = [];

					$employersStr = trim($_POST['empStr'], '||');

					if(mb_strpos($employersStr,'||') !== false){
						$arEmployers = explode('||', $employersStr);
					}
					else{
						$arEmployers[0] = $employersStr;	
					}

					if(count($arEmployers) > 0){
						if(file_exists($EMPLOYERS_LIST_PATH)){
							$employersList = require($EMPLOYERS_LIST_PATH);
						}
						
						foreach($arEmployers as $employerId){
							unset($employersList[$employerId]);
						}

						Callsapp\CallsAppHelper::saveArrayToFile($employersList, $EMPLOYERS_LIST_PATH);
						
						$arResData['STATUS'] = 'success';
						$arResData['RESULT'] = $employersList;
					}
				}
			break;
		}	

	}
	else{
		$arResData['STATUS'] = 'error';
		$arResData['MESS'][] = 'Не указано действие';
	}
}
else{
	die('Скрипт не может быть вызван в браузере');
}

// возвращаем данные в JS
Callsapp\CallsAppHelper::createJsonResult($arResData);
?>
<?
    // подключение служебной части эпилога
    require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/epilog_after.php");
?>
