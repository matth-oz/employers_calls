<?php
use Mattweb\RestB24;
use Mattweb\Callsapp;


define('NO_AGENT_CHECK', true);
define('STOP_STATISTICS', true);
define('PUBLIC_AJAX_MODE', true);
define('NOT_CHECK_PERMISSIONS', true);

// https://gkcit.ru/devops/edit/in-hook/51/
// подключение служебной части пролога
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");

require_once($_SERVER["DOCUMENT_ROOT"]."/employers_calls/data/settings.php");
require_once($_SERVER["DOCUMENT_ROOT"]."/employers_calls/classes/rest_b24_department.php");
require_once($_SERVER["DOCUMENT_ROOT"]."/employers_calls/classes/callshelper.php");

/*
	params:

	portal_user_id - идентификатор сотрудника
	calls_date_from - начальная дата диапазона
	calls_date_to - конечная дата диапазона
*/


if(isset($_POST['ajax'])){

	$arResData = [];

	// CSRF: запрос должен идти со страницы приложения (валидный sessid)
	if(!check_bitrix_sessid()){
		Callsapp\CallsAppHelper::createJsonResult(['STATUS' => 'error', 'MESS' => ['Неверный токен сессии']]);
	}

	try{
		$portalUserId = (int)($_POST['portal_user_id'] ?? 0);

		// сотрудник обязан быть из утверждённого списка (закрывает перебор чужих звонков)
		$employersListPath = $_SERVER['DOCUMENT_ROOT'].'/employers_calls/data/employers_list.php';
		$employersList = is_file($employersListPath) ? require($employersListPath) : [];
		if($portalUserId <= 0 || !is_array($employersList) || !array_key_exists($portalUserId, $employersList)){
			Callsapp\CallsAppHelper::createJsonResult(['STATUS' => 'error', 'MESS' => ['Недопустимый сотрудник']]);
		}

		// даты строго в формате Y-m-d
		$dFrom = \DateTime::createFromFormat('Y-m-d', trim($_POST['calls_date_from'] ?? ''));
		$dTo   = \DateTime::createFromFormat('Y-m-d', trim($_POST['calls_date_to'] ?? ''));
		if(!$dFrom || !$dTo){
			Callsapp\CallsAppHelper::createJsonResult(['STATUS' => 'error', 'MESS' => ['Неверный формат даты']]);
		}
		$callDateFrom = $dFrom->format('Y-m-d').' 00:00:00';
		$callDateTo   = $dTo->format('Y-m-d').' 23:59:59';

		$bxDptAPI = new RestB24\RestBx24Department(C_REST_WEB_HOOK_URL);

		if(!empty($portalUserId)){
			$arUsers = [];
			$usersResult = $bxDptAPI->callMethod(
				'user.get',
				[
					'FILTER' => [
						"ACTIVE" => 'Y',
						"UF_DEPARTMENT" => 98,
						"USER_TYPE" => 'employee' 				
					],
					'SORT' => 'ID',
					'ORDER' => 'ASC',
				]
			);

			foreach($usersResult['result'] as $arUser){
				$arUsers[$arUser['ID']] = $arUser['NAME'].' '.$arUser['LAST_NAME'];
			}

			$arResult = $arResultTmp = $arCrmEntitiesCalls = [];

			$result = $bxDptAPI->callMethod('voximplant.statistic.get',
			[
				'FILTER' => [
					'PORTAL_USER_ID' => $portalUserId,
					'>=CALL_START_DATE' => date($callDateFrom),
					'<=CALL_START_DATE' => date($callDateTo),
				],
				'SORT' => 'CALL_START_DATE',
				'ORDER' => 'DESC',
			]
		);

		if(sizeof($result['result']) > 0){
			foreach($result['result'] as $arrCall){
				$callDate = '';
				
				$isoDate = $arrCall['CALL_START_DATE'];			
				$format = 'd.m.Y H:i';
				$timestamp = strtotime($isoDate);
				if($timestamp !== false){
					$callDate = date($format, $timestamp);
				}
				
				$crmEntityType = $arrCall['CRM_ENTITY_TYPE'];
				if($crmEntityType != ''){
					$arCrmEntitiesCalls[$crmEntityType][$arrCall['CRM_ENTITY_ID']] = [
						'CALL_ID' => $arrCall['ID'],
						'ENTITY_ID' => $arrCall['CRM_ENTITY_ID'],
						'ENTITY_TITLE' => '',
						'COMPANY_ID' => '',
						'COMPANY_TITLE' => '',
					];
				}		

				$arResultTmp[$arrCall['ID']] = [
					'PORTAL_USER' => $arUsers[$arrCall['PORTAL_USER_ID']], // Сотрудник	
					'PORTAL_NUMBER' => $arrCall['PORTAL_NUMBER'], // Номер в Битрикс24
					'PHONE_NUMBER' => $arrCall['PHONE_NUMBER'], // Номер телефона
					'CALL_TYPE' => Callsapp\CallsAppHelper::$arCallTypes[$arrCall['CALL_TYPE']], // Тип звонка
					'CALL_DURATION' => $arrCall['CALL_DURATION'], // Время звонка
					'CALL_START_DATE' => $callDate, /* $arrCall['CALL_START_DATE'], // Дата вызова */
					'CRM_ENTITY_TYPE' => $crmEntityType,
					'CRM_ENTITY_ID' => $arrCall['CRM_ENTITY_ID'],				
				];

				// Статус
				$callStatus = Callsapp\CallsAppHelper::getCallStatus($arrCall['CALL_FAILED_REASON']);
				$arResultTmp[$arrCall['ID']]['CALL_STATUS'] = $callStatus;
			}
		}
		
		$totalCount = $result['total'];

		if($totalCount > 50){
			$totalPagesCount = IntVal(ceil($totalCount / 50)) - 1;

			for($i = 1; $i <= $totalPagesCount; $i++){

				$startPos = $i * 50;
				$result = $bxDptAPI->callMethod('voximplant.statistic.get',
					[
						'FILTER' => [
							'PORTAL_USER_ID' => $portalUserId, 
							'>=CALL_START_DATE' => date($callDateFrom),
							'<=CALL_START_DATE' => date($callDateTo),
						],
						'SORT' => 'CALL_START_DATE',
						'ORDER' => 'DESC',
						'start' => $startPos
					]
				);

				/*echo '<pre>';
				echo '<hr />';
				print_r($result);
				echo '</pre>';*/

				if(sizeof($result['result']) > 0){
					foreach($result['result'] as $arrCall){
						$callDate = '';
						
						$isoDate = $arrCall['CALL_START_DATE'];			
						$format = 'd.m.Y H:i';
						$timestamp = strtotime($isoDate);
						if($timestamp !== false){
							$callDate = date($format, $timestamp);
						}
						
						$crmEntityType = $arrCall['CRM_ENTITY_TYPE'];
						if($crmEntityType != ''){
							$arCrmEntitiesCalls[$crmEntityType][$arrCall['CRM_ENTITY_ID']] = [
								'CALL_ID' => $arrCall['ID'],
								'ENTITY_ID' => $arrCall['CRM_ENTITY_ID'],
								'ENTITY_TITLE' => '',
								'COMPANY_ID' => '',
								'COMPANY_TITLE' => '',
							];
						}				

						$arResultTmp[$arrCall['ID']] = [
							'PORTAL_USER' => $arUsers[$arrCall['PORTAL_USER_ID']], // Сотрудник								
							'PORTAL_NUMBER' => $arrCall['PORTAL_NUMBER'], // Номер в Битрикс24
							'PHONE_NUMBER' => $arrCall['PHONE_NUMBER'], // Номер телефона
							'CALL_TYPE' => Callsapp\CallsAppHelper::$arCallTypes[$arrCall['CALL_TYPE']], // Тип звонка
							'CALL_DURATION' => $arrCall['CALL_DURATION'], // Время звонка
							'CALL_START_DATE' => $callDate, /* $arrCall['CALL_START_DATE'], // Дата вызова */
							'CRM_ENTITY_TYPE' => $crmEntityType,
							'CRM_ENTITY_ID' => $arrCall['CRM_ENTITY_ID'],
						];

						// Статус
						$callStatus = Callsapp\CallsAppHelper::getCallStatus($arrCall['CALL_FAILED_REASON']);
						$arResultTmp[$arrCall['ID']]['CALL_STATUS'] = $callStatus;
					}
				}

				if(!array_key_exists('next', $result)) break;			
			}
		}
		
		$arKeys = array_keys($arCrmEntitiesCalls);

		// Пакетная предзагрузка сущностей CRM (вместо N+1 запросов .get на каждый звонок)
		$contactIds = array_keys($arCrmEntitiesCalls['CONTACT'] ?? []);
		$leadIds    = array_keys($arCrmEntitiesCalls['LEAD'] ?? []);
		$contacts = $leads = $companies = [];
		$companyIds = [];

		foreach(array_chunk($contactIds, 50) as $chunk){
			$res = $bxDptAPI->callMethod('crm.contact.list', [
				'FILTER' => ['ID' => $chunk],
				'SELECT' => ['ID', 'NAME', 'SECOND_NAME', 'LAST_NAME', 'COMPANY_ID'],
			]);
			foreach(($res['result'] ?? []) as $c){
				$contacts[$c['ID']] = $c;
				if((int)$c['COMPANY_ID'] > 0){ $companyIds[(int)$c['COMPANY_ID']] = true; }
			}
		}

		foreach(array_chunk($leadIds, 50) as $chunk){
			$res = $bxDptAPI->callMethod('crm.lead.list', [
				'FILTER' => ['ID' => $chunk],
				'SELECT' => ['ID', 'TITLE', 'COMPANY_ID'],
			]);
			foreach(($res['result'] ?? []) as $l){
				$leads[$l['ID']] = $l;
				if((int)$l['COMPANY_ID'] > 0){ $companyIds[(int)$l['COMPANY_ID']] = true; }
			}
		}

		foreach(array_chunk(array_keys($companyIds), 50) as $chunk){
			$res = $bxDptAPI->callMethod('crm.company.list', [
				'FILTER' => ['ID' => $chunk],
				'SELECT' => ['ID', 'TITLE'],
			]);
			foreach(($res['result'] ?? []) as $co){
				$companies[$co['ID']] = $co['TITLE'];
			}
		}

		foreach($arKeys as $key){

			foreach($arCrmEntitiesCalls[$key] as &$arCrmEntityCall){
				$entId = $arCrmEntityCall['ENTITY_ID'];
				if($key == 'CONTACT'){
					$resEntity = ['result' => $contacts[$entId] ?? []];

					if(!empty($resEntity['result'])){

						//var_dump($resEntity['result']);

						$arCrmEntityCall['ENTITY_TITLE'] = $resEntity['result']['NAME'];

						if(!empty($resEntity['result']['SECOND_NAME'])){
							$arCrmEntityCall['ENTITY_TITLE'] .= ' '.$resEntity['result']['SECOND_NAME'];
						}

						$arCrmEntityCall['ENTITY_TITLE'] .= ' '.$resEntity['result']['LAST_NAME'];				

						$companyId = (int) $resEntity['result']['COMPANY_ID'];
						
						if($companyId > 0){
							$arCrmEntityCall['COMPANY_ID'] = $companyId;
							if(isset($companies[$companyId])){
								$arCrmEntityCall['COMPANY_TITLE'] = $companies[$companyId];
							}
						}
					}
					
				}

				if($key == 'LEAD'){
					$resEntity = ['result' => $leads[$entId] ?? []];

					if(!empty($resEntity['result'])){
						$arCrmEntityCall['ENTITY_TITLE'] = $resEntity['result']['TITLE'];

						$companyId = (int) $resEntity['result']['COMPANY_ID'];

						if($companyId > 0){
							$arCrmEntityCall['COMPANY_ID'] = $companyId;

							if(isset($companies[$companyId])){
								$arCrmEntityCall['COMPANY_TITLE'] = $companies[$companyId];
							}
						}

					}
				}

			}

			// echo $key.' = '.count($arCrmEntitiesCalls[$key]);
		}

		foreach($arResultTmp as $key => $arItemTmp){
			
			$callDuraion = '';
			if($arItemTmp['CALL_DURATION'] > 0){
				$minutes = floor($arItemTmp['CALL_DURATION'] / 60);
				$callDuraion .= $minutes.' мин';
				$seconds = $arItemTmp['CALL_DURATION'] % 60;
				$callDuraion .= ', '.$seconds.' сек';
			}

			// Сотрудник
			$arResult[$key]['PORTAL_USER'] = $arItemTmp['PORTAL_USER'];
			// Номер в Битрикс24
			$arResult[$key]['PORTAL_NUMBER'] = $arItemTmp['PORTAL_NUMBER'];
			// Номер телефона
			$arResult[$key]['PHONE_NUMBER'] = $arItemTmp['PHONE_NUMBER'];		
			// Тип звонка
			$arResult[$key]['CALL_TYPE'] = $arItemTmp['CALL_TYPE'];
			// Время звонка
			$arResult[$key]['CALL_DURATION'] = $callDuraion;		
			// Дата вызова
			$arResult[$key]['CALL_START_DATE'] = $arItemTmp['CALL_START_DATE'];
			// Статус
			$arResult[$key]['CALL_STATUS'] = $arItemTmp['CALL_STATUS'];
			// Лид или контакт
			if($arItemTmp['CRM_ENTITY_TYPE'] == 'CONTACT'){
				$arResult[$key]['CRM_ENTITY'] = 'Контакт: ';
			}
			elseif($arItemTmp['CRM_ENTITY_TYPE'] == 'LEAD'){
				$arResult[$key]['CRM_ENTITY'] = 'Лид: ';
			}
			else{
				$arResult[$key]['CRM_ENTITY'] = '-';
			}
			
			if($arResult[$key]['CRM_ENTITY'] != '-'){
				$arResult[$key]['CRM_ENTITY'] .= $arCrmEntitiesCalls[$arItemTmp['CRM_ENTITY_TYPE']][$arItemTmp['CRM_ENTITY_ID']]['ENTITY_TITLE'];
			}	

			$companyTitle = $arCrmEntitiesCalls[$arItemTmp['CRM_ENTITY_TYPE']][$arItemTmp['CRM_ENTITY_ID']]['COMPANY_TITLE'];
			// Компания
			$arResult[$key]['CRM_ENTITY_COMPANY'] = !empty($companyTitle) ? $companyTitle : '-';		
		}

		$arResData['STATUS'] = 'success';
		$arResData['RESULT'] = $arResult;

		$source = bin2hex(random_bytes(16)).'.json';
		$arResData['SOURCE'] = $source;

		Callsapp\CallsAppHelper::saveJsonToFile($arResult, $_SERVER["DOCUMENT_ROOT"].'/employers_calls/tmp/'.$source);

		/*$dataToSave = '<? return ';
		$dataToSave .= var_export($arResult, true);
		$dataToSave .= ';';

		file_put_contents($_SERVER["DOCUMENT_ROOT"].'/employers_calls/tmp/'.$source, $dataToSave, FILE_APPEND|LOCK_EX);*/

		}
		else{
			$arResData['STATUS'] = 'error';
			$arResData['MESS'][] = 'Не указан сотрудник';
		}

	}catch(\Throwable $e){
		$arResData = ['STATUS' => 'error', 'MESS' => ['Ошибка обращения к Bitrix24: ' . $e->getMessage()]];
	}
}
else{
	die('Скрипт не может быть вызван в браузере');
}



// возвращаем данные в JS
Callsapp\CallsAppHelper::createJsonResult($arResData);

/*header('Content-Type: application/json; charset='.LANG_CHARSET);
echo \Bitrix\Main\Web\Json::encode($arResData);
die();*/
?>
<?
    // подключение служебной части эпилога
    require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/epilog_after.php");
?>
