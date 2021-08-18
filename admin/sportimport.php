<?php
require_once($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/prolog_admin_before.php");
require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/prolog_admin_after.php");


global $APPLICATION;
$APPLICATION->SetTitle('Импорт команд и участников спортакиады');


use \Bitrix\Main\Loader;
use \Dmbgeo\SportImport\Import;

$strError = "";
$logs = "";
if (!Loader::IncludeModule("iblock")) {
    $strError .= "Модуль iblock не установлен <br>";
}

if (!Loader::IncludeModule("dmbgeo.sportimport")) {
    $strError .= "Модуль dmbgeo.sportimport не установлен <br>";
}


require_once($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/iblock/prolog.php");
IncludeModuleLangFile(__FILE__);
$success = false;
$command_success = 0;
if ($_SERVER["REQUEST_METHOD"] == "POST" && check_bitrix_sessid()) {
    CUtil::JSPostUnescape();
    if (empty($_REQUEST['URL_DATA_FILE_TEAMS'])) {
        $strError .= "Файл команд не выбран <br>";
    }
    if (empty($_REQUEST['URL_DATA_FILE_PLAYERS'])) {
        $strError .= "Файл участников не выбран <br>";
    }


    if ($strError == '') {
        $_REQUEST['STEP'] = intVal($_REQUEST['STEP']);
        $_REQUEST['COUNT'] = intVal($_REQUEST['COUNT']);
        
        if ($_REQUEST['STEP'] == -1) {
            Import::clearCache();
            Import::setLogs(array());
        }
        $_REQUEST['STEP']++;
        $logs = Import::getLogs();
        $offset = $_REQUEST['STEP'] * $_REQUEST['COUNT'];
        $data = Import::getData($_SERVER["DOCUMENT_ROOT"] . $_REQUEST['URL_DATA_FILE_TEAMS'], $_SERVER["DOCUMENT_ROOT"] . $_REQUEST['URL_DATA_FILE_PLAYERS'], $logs);
     
        if ($offset < count($data)) {

            $result = Import::run(array_slice($data, $offset, $_REQUEST['COUNT'], true), $logs);
            if ($result) {
                $command_success = $offset + $_REQUEST['COUNT'];
?>
                <script type="text/javascript">
                    function DoNext() {
                        document.getElementById("dataload").submit();
                    }
                    setTimeout('DoNext()', 2000);
                </script>
        <?
            } else {
                $strError .= implode("<br>", $logs);
            }
        } elseif ($_REQUEST['STEP'] > 0) {
            $command_success = count($data);
            $success = true;
        } else {
            $strError .= implode("<br>", $logs);
        }
    }
}

$aTabs = array(
    array(
        "DIV" => "form",
        "TAB" => 'Импорт данных',
        "ICON" => "iblock",
        "TITLE" => 'Импорт данных в формате csv',
    )
);
if ($strError) {
    CAdminMessage::ShowMessage($strError);
    $_REQUEST['STEP'] = '-1';
}
$tabControl = new CAdminTabControl("tabControl", $aTabs, false, true);
$tabControl->Begin();
$tabControl->BeginNextTab();

if (!$success) :

    if ($_SERVER["REQUEST_METHOD"] == "POST" && intval($_REQUEST['STEP']) >= 0) :
        ?>
        <tr>
            <td>
                <? CAdminMessage::ShowMessage(array(
                    "TYPE" => "PROGRESS",
                    "MESSAGE" => 'Продолжение пошаговой загрузки...',
                    "DETAILS" =>
                    'Всего обработано команд: <b>' . $command_success . '</b><br>',
                    "HTML" => true,
                )) ?>
            </td>
        </tr>
    <? endif; ?>
    <form method="POST" action="<? echo $APPLICATION->GetCurPageParam('lang=' . LANGUAGE_ID) ?>" enctype="multipart/form-data" name="dataload" id="dataload">
        <input type="hidden" name="STEP" value="<?= isset($_REQUEST['STEP']) ? $_REQUEST['STEP'] : '-1'; ?>">
        <input type="hidden" name="import" value="Y">
        <tr>
            <td width="50%">Количество обработки команд за раз </td>
            <td width="50%"><input type="text" name="COUNT" value="<?= !empty($_REQUEST['COUNT']) ? $_REQUEST['COUNT'] : 10; ?>" size="10"></td>
        </tr>
        <tr>
            <td width="40%">Файл команд</td>
            <td width="60%">
                <input type="text" name="URL_DATA_FILE_TEAMS" value="<? echo htmlspecialcharsbx($_REQUEST['URL_DATA_FILE_TEAMS']); ?>" size="30">
                <input type="button" value="Открыть" OnClick="BtnClickTeams()">
                <? CAdminFileDialog::ShowScript(array(
                    "event" => "BtnClickTeams",
                    "arResultDest" => array(
                        "FORM_NAME" => "dataload",
                        "FORM_ELEMENT_NAME" => "URL_DATA_FILE_TEAMS",
                    ),
                    "arPath" => array(
                        "SITE" => SITE_ID,
                        "PATH" => "/" . COption::GetOptionString("main", "upload_dir", "upload"),
                    ),
                    "select" => 'F', // F - file only, D - folder only
                    "operation" => 'O', // O - open, S - save
                    "showUploadTab" => true,
                    "showAddToMenuTab" => false,
                    "fileFilter" => 'csv',
                    "allowAllFiles" => true,
                    "SaveConfig" => true,
                ));
                ?>
            </td>
        </tr>
        <tr>
            <td width="40%">Файл участников</td>
            <td width="60%">
                <input type="text" name="URL_DATA_FILE_PLAYERS" value="<? echo htmlspecialcharsbx($_REQUEST['URL_DATA_FILE_PLAYERS']); ?>" size="30">
                <input type="button" value="Открыть" OnClick="BtnClickPlayers()">
                <? CAdminFileDialog::ShowScript(array(
                    "event" => "BtnClickPlayers",
                    "arResultDest" => array(
                        "FORM_NAME" => "dataload",
                        "FORM_ELEMENT_NAME" => "URL_DATA_FILE_PLAYERS",
                    ),
                    "arPath" => array(
                        "SITE" => SITE_ID,
                        "PATH" => "/" . COption::GetOptionString("main", "upload_dir", "upload"),
                    ),
                    "select" => 'F', // F - file only, D - folder only
                    "operation" => 'O', // O - open, S - save
                    "showUploadTab" => true,
                    "showAddToMenuTab" => false,
                    "fileFilter" => 'csv',
                    "allowAllFiles" => true,
                    "SaveConfig" => true,
                ));
                ?>
            </td>
        </tr>



        <? $tabControl->Buttons(); ?>
        <input type="submit" class="adm-btn" name="opts_reset" value="Импортировать">
        <?= bitrix_sessid_post(); ?>
    </form>
<? endif; ?>
<? if ($success) : ?>
    <?
    CAdminMessage::ShowMessage(array(
        "MESSAGE" => "Импорт завершен успешно",
        "DETAILS" => 'Всего обработано команд: <b>' . $command_success . '</b><br>',
        "HTML" => true,
        "TYPE" => "OK",
    ));
    ?>
<? endif; ?>
<?php

$tabControl->EndTab();
$tabControl->End();
require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/epilog_admin.php");
?>