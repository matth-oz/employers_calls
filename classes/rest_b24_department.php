<?php

namespace Mattweb\Restb24;
require_once($_SERVER["DOCUMENT_ROOT"]."/calls_gkcit_app/classes/rest_b24.php");

class RestBx24Department extends RestBx24{

    /**
     * Получение всех департаментов из структуры компании
     * @param bool $onlyActive Только активные департаменты
     * @return array Массив департаментов
     */
    public function getAllDepartments($onlyActive = true)
    {
        $allDepartments = [];
        $start = 0;
        
        try {
            do {
                $params = [
                    'start' => $start,
                    'order' => ['ID' => 'ASC']
                ];
                
                // Если нужно получить только активные департаменты
                if ($onlyActive) {
                    $params['filter'] = ['ACTIVE' => 'Y'];
                }
                
                $response = $this->callMethod('department.get', $params);
                
                if (isset($response['result']) && is_array($response['result'])) {
                    $allDepartments = array_merge($allDepartments, $response['result']);
                }
                
                // Проверяем, есть ли еще данные
                if (isset($response['next']) && $response['next'] > 0) {
                    $start = $response['next'];
                } else {
                    $start = null;
                }
                
            } while ($start !== null);
            
            return $allDepartments;
            
        } catch (Exception $e) {
            echo "Ошибка при получении департаментов: " . $e->getMessage();
            return [];
        }
    }

    /**
     * Получение департаментов с иерархической структурой
     * @return array Древовидная структура департаментов
     */
    public function getDepartmentTree()
    {
        $departments = $this->getAllDepartments();
        $tree = [];
        $indexedDepartments = [];
        
        // Индексируем департаменты по ID
        foreach ($departments as $dept) {
            $indexedDepartments[$dept['ID']] = $dept;
            $indexedDepartments[$dept['ID']]['children'] = [];
        }
        
        // Строим дерево
        foreach ($indexedDepartments as $id => &$dept) {
            if (isset($dept['PARENT']) && $dept['PARENT'] > 0 && isset($indexedDepartments[$dept['PARENT']])) {
                $indexedDepartments[$dept['PARENT']]['children'][] = &$dept;
            } else {
                $tree[] = &$dept;
            }
        }
        
        return $tree;
    }

    /**
     * Получение информации о конкретном департаменте
     * @param int $departmentId ID департамента
     * @return array|null Информация о департаменте или null
     */
    public function getDepartmentById($departmentId)
    {
        try {
            $response = $this->callMethod('department.get', [
                'filter' => ['ID' => $departmentId]
            ]);
            
            return isset($response['result'][0]) ? $response['result'][0] : null;
            
        } catch (Exception $e) {
            echo "Ошибка при получении департамента: " . $e->getMessage();
            return null;
        }
    }

}








    