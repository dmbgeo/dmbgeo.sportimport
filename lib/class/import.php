<?php



namespace Dmbgeo\SportImport;

class Import
{

    public static function csv_to_array($filename = '', $delimiter = ';')
    {
        if (!file_exists($filename) || !is_readable($filename))
            return false;

        $header = NULL;
        $data = array();
        if (($handle = fopen($filename, 'r')) !== false) {
            while (($row = fgets($handle)) !== false) {
                $row = explode($delimiter, $row);
                foreach ($row as $key => $val) {

                    $row[$key] = trim(trim($val), '"');
                }
                if (!$header)
                    $header = $row;
                else {
                    $dataRow = array();
                    foreach ($header as $key => $val) {
                        if ($row[$key]) {
                            $dataRow[$val] = $row[$key];
                        }
                    }
                    $data[] = $dataRow;
                }
            }
            if (!feof($handle)) {
                return array();
            }
            fclose($handle);
        }
        return $data;
    }

    public static function getIdSection($command, &$logs = array())
    {
        $teamDB = \CIBlockSection::GetList(array(), array('IBLOCK_ID' => TEAMS_IBLOCK_ID, 'NAME' => $command['name']));
        if ($team = $teamDB->Fetch()) {
            $SECTION_ID = $team['ID'];
        } else {
            $bs = new \CIBlockSection;
            $SECTION_ID = $bs->Add(array('IBLOCK_ID' => TEAMS_IBLOCK_ID, 'NAME' => $command['name'], 'ACTIVE' => 'Y'));
            $logs[] = 'Импортирована команда — [' . $SECTION_ID . '] ' . $command['name'];
        }
        return $SECTION_ID;
    }

    public static function getIdPlayer($command, $player, &$logs = array())
    {
        global $USER;
        $el = new \CIBlockElement;
        $PROP = array();
        $PROP['DATE'] = date('d.m.Y H:i:s', strtotime($player['date']));
        $PROP['PHONE'] = $player['phone'];

        $arLoadProductArray = array(
            "MODIFIED_BY" => $USER->GetID(),
            "IBLOCK_SECTION_ID" => false,
            "IBLOCK_ID" => PLAYERS_IBLOCK_ID,
            "PROPERTY_VALUES" => $PROP,
            "NAME" => $player['name'],
            "ACTIVE" => "Y",
        );
        $playerDB = \CIBlockElement::GetList(array(), array('IBLOCK_ID' => PLAYERS_IBLOCK_ID, 'NAME' => $player['name']));
        if ($player = $playerDB->Fetch()) {
            $PLAYER_ID = $player['ID'];
            $el->update($player['ID'], $arLoadProductArray);
            $logs[] = 'Обновлен участник из команды ' . $command['name'] . ' — [' . $PLAYER_ID . '] ' . $player['NAME'];
        } else {
            $PLAYER_ID = $el->Add($arLoadProductArray);
            $logs[] = 'Импортирован участник из команды ' . $command['name'] . ' — [' . $PLAYER_ID . '] ' . $player['NAME'];
        }

        return $PLAYER_ID;
    }

    public static function getIdTeam($SECTION_ID, $command, $sport, $PLAYER_IDS, &$logs = array())
    {
        global $USER;
        $PROP = array();
        $PROP["COMMAND_NAME"] = $command['name'];
        $types = \CIBlockPropertyEnum::GetList([], [
            "IBLOCK_ID" => TEAMS_IBLOCK_ID,
            "VALUE" => $command['type'],
            'PROPERTY_ID' => TEAMS_IBLOCK_PROPERTY_TYPE_ID,
        ]);
        if ($type = $types->Fetch()) {
            $PROP["TYPE"]['VALUE'] = $type["ID"];
        } else {
            $ibpenum = new \CIBlockPropertyEnum;
            $PROP["TYPE"]['VALUE'] = $ibpenum->Add(array('IBLOCK_ID' => TEAMS_IBLOCK_ID, 'PROPERTY_ID' => TEAMS_IBLOCK_PROPERTY_TYPE_ID, 'VALUE' => $command['type']));
        }

        $PROP["EMAIL"] = $command['email'];
        $PROP["PHONE"] = $command['phone'];
        $PROP["TYPE"] = $command['phone'];

        $PROP["PLAYERS"] = $PLAYER_IDS;

        $arLoadProductArray = array(
            "MODIFIED_BY"    => $USER->GetID(),
            "IBLOCK_SECTION_ID" => $SECTION_ID,
            "IBLOCK_ID"      => TEAMS_IBLOCK_ID,
            "PROPERTY_VALUES" => $PROP,
            "NAME"           => $sport['name'],
            "ACTIVE"         => "Y",
        );

        $el = new \CIBlockElement;

        $teamDB = \CIBlockElement::GetList(array(), array('IBLOCK_ID' => TEAMS_IBLOCK_ID, 'IBLOCK_SECTION_ID' => $SECTION_ID, 'NAME' => $sport['name']));
        if ($team = $teamDB->Fetch()) {
            $TEAM_ID = $team['ID'];
            $el->Update($TEAM_ID, $arLoadProductArray);
            $logs[] = 'Обновлен вид спорта из команды ' . $command['name'] . ' — [' . $TEAM_ID . '] ' . $sport['name'];
        } else {
            $TEAM_ID = $el->Add($arLoadProductArray);
            $logs[] = 'Импортирован вид спорта из команды ' . $command['name'] . ' — [' . $TEAM_ID . '] ' . $sport['name'];
        }

        return $TEAM_ID;
    }

    public static function clearCache()
    {
        $cache = \Bitrix\Main\Data\Cache::createInstance();
        $cache->clean('data', 'dmbgeo.sportimport');
    }

    public static function clearLogs()
    {
        $cache = \Bitrix\Main\Data\Cache::createInstance();
        $cache->clean('logs', 'dmbgeo.sportimport');
    }


    public static function setLogs($logs)
    {
        self::clearLogs();
        $cache = \Bitrix\Main\Data\Cache::createInstance();
        if ($cache->initCache(36000, 'logs', 'dmbgeo.sportimport')) {
            $logs = $cache->getVars();
        } elseif ($cache->startDataCache()) {
            $cache->endDataCache($logs);
        }
    }

    public static function getLogs()
    {

        $cache = \Bitrix\Main\Data\Cache::createInstance();
        if ($cache->initCache(36000, 'logs', 'dmbgeo.sportimport')) {
            $logs = $cache->getVars();
        } else {
            $logs = array();
        }
        return $logs;
    }

    public static function getData($path_file_teams, $path_file_players, &$logs)
    {

        $cache = \Bitrix\Main\Data\Cache::createInstance();
        if ($cache->initCache(3600, 'data', 'dmbgeo.sportimport')) {
            $data = $cache->getVars();
        } elseif ($cache->startDataCache()) {

            $players = self::csv_to_array($path_file_players);

            $users = self::csv_to_array($path_file_teams);

            // $data = array();

            // if (is_array($players) && is_array($users)) {
            //     foreach ($players as $player) {

            //         if (strpos($player['command'], "@") !== false && !empty($player['name'])) {
            //             $data[$player['command']]['command'] = $player['command'];
            //             $data[$player['command']]['sports'][$player['varrioussports']]['name'] = $player['varrioussports'];
            //             $data[$player['command']]['sports'][$player['varrioussports']]['players'][$player['name']]['name'] = $player['name'];
            //             $data[$player['command']]['sports'][$player['varrioussports']]['players'][$player['name']]['date'] = $player['birthday_string'];
            //         }
            //     }
            //     foreach ($users as $user) {
            //         if (array_key_exists($user['name'], $data)) {
            //             $data[$user['name']]['name'] = $user['company'];
            //             $data[$user['name']]['email'] = $user['name'];
            //             $data[$user['name']]['phone'] = $user['phone'];
            //             $data[$user['name']]['type'] = $user['type'];
            //         }
            //     }
            // }



            if (!is_array($players)) {
                $logs[] = 'Ошибка файла участников';
            }
            if (!is_array($users)) {
                $logs[] = 'Ошибка файла команд';
            }

            $data = array();


            foreach ($players as $player) {

                if (strpos($player['command'], "@") !== false && !empty($player['name'])) {

                    $data[$player['command']]['command'] = $player['command'];
                    $data[$player['command']]['sports'][$player['varrioussports']]['name'] = $player['varrioussports'];
                    $data[$player['command']]['sports'][$player['varrioussports']]['players'][$player['name']]['name'] = $player['name'];
                    $data[$player['command']]['sports'][$player['varrioussports']]['players'][$player['name']]['date'] = $player['birthday_string'];
                    $data[$player['command']]['sports'][$player['varrioussports']]['players'][$player['name']]['phone'] = $player['phone'];
                }
            }
            foreach ($users as $user) {
                if (array_key_exists($user['name'], $data)) {
                    $data[$user['name']]['name'] = $user['company'];
                    $data[$user['name']]['email'] = $user['name'];
                    $data[$user['name']]['phone'] = $user['phone'];
                    $data[$user['name']]['type'] = $user['type'];
                }
            }

            $cache->endDataCache($data);
        }

        return $data;
    }

    public static function run($data, &$logs)
    {
        if (!empty($logs)) {
            return false;
        }

        if (\Bitrix\Main\Loader::includeModule('iblock')) {

            foreach ($data as $command) {
                $SECTION_ID = self::getIdSection($command, $logs);
                foreach ($command['sports'] as $sport) {

                    $PLAYER_IDS = array();
                    foreach ($sport['players'] as $player) {
                        $PLAYER_IDS[] = self::getIdPlayer($command, $player, $logs);
                        unset($player);
                    }

                    self::getIdTeam($SECTION_ID, $command, $sport, $PLAYER_IDS, $logs);
                    unset($PLAYER_IDS);
                    unset($sport);
                }
                unset($SECTION_ID);
                unset($command);
            }
        }

        if (!empty($logs)) {
            return true;
        } else {
            $logs[] = 'Ошибка, проверте данные';
            return false;
        }
    }
}
