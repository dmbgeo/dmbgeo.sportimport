<?php

$_SERVER["DOCUMENT_ROOT"] = realpath(dirname(__FILE__) . "/../../..");
define("NO_KEEP_STATISTIC", true);
define("NOT_CHECK_PERMISSIONS", true);
define("CHK_EVENT", true);

require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/prolog_before.php");

@set_time_limit(0);

use \Dmbgeo\SportImport\AutoImport;
use \Dmbgeo\SportImport\Import;
use \Bitrix\Main\Loader;

$dir = $_SERVER["DOCUMENT_ROOT"] .'/'. COption::GetOptionString("main", "upload_dir", "upload") . '/csv_import';
if (!is_dir($dir)) {
    mkdir($dir);
}
if (Loader::includeModule('dmbgeo.sportimport')) {
    $autoImport = new AutoImport;
    $users_file_path = false;
    $players_file_path = false;
    if ($autoImport->getUsers($dir . '/users.csv')) {
        $users_file_path = $dir . '/users.csv';
    }
    if ($autoImport->getPlayers($dir . '/player.csv')) {
        $players_file_path = $dir . '/player.csv';
    }


    $strError = "";
    $logs = "";
    if (!Loader::IncludeModule("main")) {
        $strError .= "Модуль main не установлен <br>";
    }

    if (!Loader::IncludeModule("iblock")) {
        $strError .= "Модуль iblock не установлен <br>";
    }

    if (!Loader::IncludeModule("dmbgeo.sportimport")) {
        $strError .= "Модуль dmbgeo.sportimport не установлен <br>";
    }

    $autoImport->dump_file("/" . COption::GetOptionString("main", "upload_dir", "upload"));


    IncludeModuleLangFile(__FILE__);
    $success = false;
    $command_success = 0;
    CUtil::JSPostUnescape();
    if (empty($users_file_path)) {
        $strError .= "Файл команд не выбран <br>";
    }
    if (empty($players_file_path)) {
        $strError .= "Файл участников не выбран <br>";
    }

    $STEP = -1;
    $COUNT = 10;
    if ($strError == '') {
        $autoImport->dump_file($users_file_path);
        $autoImport->dump_file($players_file_path);
        while (true) {
            if ($STEP == -1) {
                Import::clearCache();
                Import::setLogs(array());
                $autoImport->dump_file('clear cache');
            }
            $STEP++;
            $logs = Import::getLogs();
            $offset = $STEP * $COUNT;
            $data = Import::getData($users_file_path, $players_file_path, $logs);

            $autoImport->dump_file(array('STEP' => $STEP, 'offset' => $offset, 'COUNT' => $COUNT, 'SLICE' => array_slice($data, $offset, $COUNT, true)));

            if ($offset < count($data)) {
                $result = Import::run(array_slice($data, $offset, $COUNT, true), $logs);
                if ($result) {
                    $command_success = $offset + $COUNT;
                } else {
                    break;
                }
            } else {
                break;
            }
        }
        $autoImport->dump_file($logs);
    } else {
        $autoImport->dump_file($strError);
    }
}
