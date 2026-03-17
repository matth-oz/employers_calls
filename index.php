<?
    // подключение служебной части пролога
    require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");
    
    if(file_exists(__DIR__.'/data/settings.php')){
        $employersList = require(__DIR__.'/data/settings.php');
    }

    $employersList = [];
    if(file_exists(__DIR__.'/data/employers_list.php')){
        $employersList = require(__DIR__.'/data/employers_list.php');
    }
?>
<!doctype html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Звонки сотрудника за месяц</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">
    <link href="./css/calls.css" rel="stylesheet">
    <script src="./js/calls.js"></script>
  </head>
  <body>

<pre>
    <?//var_export($result)?>
</pre>

    <div class="container">
        <header>
           <h2>Звонки сотрудника за период</h2>   
        </header>
        <div class="row justify-content-start">
            <div class="col-6">
                <?if(count($employersList) > 0):?>

                <form class="j-calls-filter calls-filter" action="./ajax/calls_ajax.php" method="post">
                    <label class="form-label">
                        <span class="lbl">Выберите сотрудника:</span>
                        <select class="form-select form-select-sm" name="portal_user_id" id="portal_user_id">
                            <option>---</option>
                            <?foreach($employersList as $arEmployer):?>
                                <option value="<?=$arEmployer['ID']?>"<?if($arEmployer['ID'] == DEFAULT_EMPLOYER_ID):?> selected<?endif?>><?=$arEmployer['NAME']?></option>
                            <?endforeach?>                                            
                        </select>
                        <span class="j-err-mess err-mess vh">Не выбран сотрудник</span>
                    </label>
                    <div class="calls-period">
                        <label class="form-label">
                            <span class="lbl">Период, от:</span>
                            <input type="date" name="calls_date_from" class="form-control" id="calls_date_from" />
                            <span class="j-err-mess err-mess vh">Не указана дата</span>
                        </label>
                        <label class="form-label">
                            <span class="lbl">Период, до:</span>
                            <input type="date" name="calls_date_to" class="form-control" id="calls_date_to" />
                            <span class="j-err-mess err-mess vh">Не указана дата</span>
                        </label>
                    </div>
                    <label class="form-label">
                        <input type="submit" name="get_calls" class="btn btn-primary btn-sm" />
                    </label>
                    <div class="j-process-block process-block spinner-border dn" role="status">
                        <span class="visually-hidden">Получение данных…</span>
                    </div>
                </form>
                <?else:?>
                    <?ShowError('Список сотрудников пуст! Обратитесь к администратору.');?>
                <?endif?>
            </div>
        </div>

        <div class="row calls-result">
            <div class="col-12">
                <div class="j-bf-tbl bf-tbl btn-wrap vh">
                    <a href="#" class="btn btn-primary btn-sm j-export-xls">Выгрузить в .xls</a>
                </div>
                <table class="table j-calls-table dn">
                    <thead>
                        <tr>
                            <th scope="col">Сотрудник</th>
                            <th scope="col">Номер в Битрикс24</th>
                            <th scope="col">Номер телефона</th>
                            <th scope="col">Тип звонка</th>
                            <th scope="col">Время звонка</th>
                            <th scope="col">Дата вызова</th>
                            <th scope="col">Статус</th>
                            <th scope="col">Лид или контакт</th>
                            <th scope="col">Компания</th>
                        </tr>
                    </thead>
                    <tbody class="j-calls-rows-root"></tbody>
                </table>
            </div>
        </div>

    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
        
            let callsTableHandler = new CallsTableHandler({
                jCallsFilter: '.j-calls-filter',
                fPortalUserId: 'portal_user_id',
                fCallsDateFrom: 'calls_date_from',
                fCallsDateTo: 'calls_date_to',
                jProcessBlock: '.j-process-block',
                jBfTbl: '.j-bf-tbl',
                jCallsTable: '.j-calls-table',
                jExportXls: '.j-export-xls',
                jCallsRowsRoot: '.j-calls-rows-root',
            });

            callsTableHandler.init();
        });
    </script>
    
  </body>
</html>

<?
    // подключение служебной части эпилога
    require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/epilog_after.php");
?>