<?php
namespace Mattweb\Restb24;

class Departmenthelper{
     public static function printDepartmentTree($tree, $level = 0) {
        foreach ($tree as $dept) {
            echo str_repeat("&nbsp;&nbsp;&nbsp;&nbsp;", $level) . 
            "├─ " . '<a href="#" class="j-deplink" data-depid="'.$dept["ID"].'">'.$dept['NAME'] .'</a>'. " (ID: " . $dept['ID'] . ")<br>";
            
            if (!empty($dept['children'])) {
                self::printDepartmentTree($dept['children'], $level + 1);
            }
        }
    }
}
