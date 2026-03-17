<?php
define('NEED_AUTH', 'Y');
use Mattweb\RestB24;

$EMPLOYERS_LIST_PATH = $_SERVER['DOCUMENT_ROOT'].'/employers_calls/data/employers_list.php';

require_once($_SERVER["DOCUMENT_ROOT"]."/employers_calls/data/settings.php");
require_once($_SERVER["DOCUMENT_ROOT"]."/employers_calls/classes/rest_b24_department.php");
require_once($_SERVER["DOCUMENT_ROOT"]."/employers_calls/classes/departmenthelper.php");
    
// подключение служебной части пролога
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");

$arDepartments = [];

$bxDptAPI = new RestB24\RestBx24Department(C_REST_WEB_HOOK_URL);

$sectRes = $bxDptAPI->callMethod(
    'department.get',
    [
        'sort' => 'NAME',
        'order' => 'DESC',
        'ACTIVE' => 'Y',
    ]
);

$tree = [];
$indexedDepartments = [];

if(!empty($sectRes['result'])){
    foreach($sectRes['result'] as $arResItem){
        $indexedDepartments[$arResItem['ID']] = $arResItem;
        $indexedDepartments[$arResItem['ID']]['children'] = [];
    }
}

foreach($indexedDepartments as $id => &$arDept){
    if (isset($arDept['PARENT']) && $arDept['PARENT'] > 0 && isset($indexedDepartments[$arDept['PARENT']])) {
            $indexedDepartments[$arDept['PARENT']]['children'][] = &$arDept;
    } else {
            $tree[] = &$arDept;
    }
}
   
// echo '<pre>';
// var_export($indexedDepartments);    
// var_export($tree);
// echo '</pre>';

$defaultDepartmentId = $tree[0]['ID'];
   
   
   if($defaultDepartmentId > 0){
        $result = $bxDptAPI->callMethod(
			'user.get',
			[
				"UF_DEPARTMENT" => $defaultDepartmentId,
				"ACTIVE" => "Y",
				"SORT" => "ID",
				"ORDER" => "asc",
			]
		);

		if(!empty($result['result'])){
			$arResult = [];
            $arCurrentChecked = [];

			if(file_exists($EMPLOYERS_LIST_PATH)){
				$employersList = require($EMPLOYERS_LIST_PATH);
			}
                        
            foreach($result['result'] as $arEmployer){
                $arResult[$arEmployer['ID']] = $arEmployer;
                
                if(is_array($employersList) && array_key_exists($arEmployer['ID'], $employersList)){
                    $arResult[$arEmployer['ID']]['CHECKED'] = 'Y';
                    $arCurrentChecked[] = $arEmployer['ID'];
                }                            
            }			
        }
   }

?>
<!doctype html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Выбор сотрудников</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">
    <link href="<?=SITE_DIR.'employers_calls/css/employers.css'?>" rel="stylesheet">   
  </head>
  <body>
    <div class="container">
        <header>
           <h2>Выбор сотрудников</h2>   
        </header>
        <div class="row justify-content-between">
            <div class="col-6 dep-tree">
                <?Restb24\Departmenthelper::printDepartmentTree($tree);?>
            </div>
            <div class="col-6 emp-list">
                <div class="j-emp-wrap emp-wrap">
                <?
                $canReset = false;
                foreach($arResult as $arItem):?>
                    <?
                        $fullName = $arItem['NAME'];
                        if(!empty($arItem['LAST_NAME'])){
                            $fullName .= ' '.$arItem['LAST_NAME'];
                        }

                        $checked = ($arItem['CHECKED'] == 'Y') ? ' checked' : '';

                        if($arItem['CHECKED'] == 'Y' && !$canReset){
                            $canReset = $canSave = true;
                        }
                    ?>
                    <label>
                        <input class="form-check-input"<?=$checked?> type="checkbox" name="employers[]" value="<?=$arItem['ID']?>" data-empname="<?=$fullName?>" />
                        <?=$fullName?>
                    </label>
                    
                <?endforeach?>                    
                </div>
                <div class="btn-wrap">
                    <button<?if(!$canSave):?> disabled<?endif?> class="j-emp-save btn btn-primary btn-sm">Сохранить</button>&nbsp;
                    <button<?if(!$canReset):?> disabled<?endif?> class="j-emp-reset btn btn-outline-primary btn-sm">Очистить</button>
                </div>
            </div>
        </div>
    </div>
	
	<script src="<?=SITE_DIR.'employers_calls/js/employers.js'?>"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            let employers = new Employers({
                depLinkSelector: '.j-deplink',
                empWrapSelector: '.j-emp-wrap',
                empSaveBtnSelector: '.j-emp-save',
                empResetBtnSelector: '.j-emp-reset',
                currenChecked: <?=CUtil::PhpToJSObject($arCurrentChecked)?>,
            });

            employers.init();
        });
    </script>


  </body>
</html>

<?
    // подключение служебной части эпилога
    require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/epilog_after.php");
?>