<?php

namespace Dmbgeo\SportImport;

use Bitrix\Main\Config\Option;
use \GuzzleHttp\Client;
use \GuzzleHttp\Psr7\Utils;

class AutoImport
{
    private $client = null;
    private $cookie = null;



    function __construct()
    {
        \Bitrix\Main\Loader::IncludeModule('main');

        $params[] = 'DMBGEO_SPORTIMPORT_BASE_PATH';
        $params[] = 'DMBGEO_SPORTIMPORT_LOGIN';
        $params[] = 'DMBGEO_SPORTIMPORT_PASSWORD';



        $client = new Client([
            'base_uri' => Option::get('dmbgeo.sportimport', 'DMBGEO_SPORTIMPORT_BASE_PATH'), // базовый uri, от него и будем двигаться дальше
            'verify'  => false,                        // если сайт использует SSL, откючаем для предотвращения ошибок
            'allow_redirects' => false,            // запрещаем редиректы
            'headers' => [                         // устанавливаем различные заголовки
                'User-Agent'   => 'Mozilla/5.0 (Linux 3.4; rv:64.0) Gecko/20100101 Firefox/15.0',
                'Accept'       => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
                'Content-Type' => 'application/x-www-form-urlencoded' // кодирование данных формы, в такой кодировке
            ]
        ]);

        $login = $client->request('POST', '/admin/users/login_do/', [
            'form_params' => [
                'from_page' => '/admin/users/users_list/',
                'login'    => Option::get('dmbgeo.sportimport', 'DMBGEO_SPORTIMPORT_LOGIN'),
                'password' => Option::get('dmbgeo.sportimport', 'DMBGEO_SPORTIMPORT_PASSWORD'),
                'lang' => 'ru'
            ]
        ]);
        $this->cookie   = $login->getHeaderLine('Set-Cookie');
        $this->client = new Client([
            'base_uri' => Option::get('dmbgeo.sportimport', 'DMBGEO_SPORTIMPORT_BASE_PATH'), // базовый uri, от него и будем двигаться дальше
            'verify'  => false,                        // если сайт использует SSL, откючаем для предотвращения ошибок
            // 'allow_redirects' => false,            // запрещаем редиректы
        ]);
    }

    private function request($method, $url, $params = array())
    {
        sleep(1);
        if ($this->client !== null && $this->cookie !== null) {
            $page = $this->client->request('GET', $url, $params);
            return  $page;
        }

        return null;
    }

    public function getUsers($path)
    {

        $queryParam = 'permissions&virtuals&viewMode=full&childs&links&templates&xmlMode=force&export=csv&used-fields[]=register_date&used-fields[]=company&used-fields[]=is_allow&used-fields[]=is_block&used-fields[]=type&used-fields[]=phone&used-fields[]=e-mail&used-fields[]=file_logo&hierarchy-level=100&lang_id[]=1&domain_id[]=1&encoding=UTF-8';

        while (true) {
            $page = $this->request('GET', '/admin/users/users_list/users_list', [
                'headers' => [
                    'accept' => 'application/json, text/javascript, */*; q=0.01',
                    'accept-encoding' => 'gzip, deflate, br',
                    'User-Agent'   => 'Mozilla/5.0 (Linux 3.4; rv:64.0) Gecko/20100101 Firefox/15.0',
                    'Cookie' => $this->cookie
                ],
                'query' => $queryParam
            ]);

            if ($page->getStatusCode() == 200) {
                $body = $page->getBody()->getContents();
                $body = json_decode($body, true);
            }

            if ($body['is_complete']) {
                $page = $this->request('GET', '/admin/users/users_list/users_list', [
                    'headers' => [
                        'accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.9',
                        'accept-encoding' => 'gzip, deflate, br',
                        'User-Agent'   => 'Mozilla/5.0 (Linux 3.4; rv:64.0) Gecko/20100101 Firefox/15.0',
                        'Cookie' => $this->cookie
                    ],
                    'decode_content' => false,
                    'query' => $queryParam . '&download=1',
                    'sink' => $path,
                    // 'debug' => true
                ]);
                if ($page->getStatusCode() == 200) {
                    return true;
                } else {
                    return false;
                }
                break;
            }
        }
        return false;
    }

    public function dump_file($message, $var = false)
    {
        if (is_array($message) || is_object($message) || $var) {
            $message = var_export($message, true);
        }
        $log = date("Y-m-d H:i:s") . " => " . $message . "\n";
        $logs_path = __DIR__ . '/logs/';
        global $DB;
        $date = date($DB->DateFormatToPHP("DD.MM.YYYY"), time());
        if (!is_dir($logs_path)) {
            mkdir($logs_path);
        }

        $result = file_put_contents($logs_path . 'logs_' . $date . '.txt', $log, FILE_APPEND);

        return $result;
    }

    public function getPlayers($path)
    {

        $queryParam = 'permissions&virtuals&viewMode=full&childs&links&templates&xmlMode=force&export=csv&used-fields[]=varrioussports&used-fields[]=command&used-fields[]=birthday_string&used-fields[]=phone&used-fields[]=okspravka&used-fields[]=work_record&hierarchy-level=100&lang_id[]=1&domain_id[]=1&encoding=UTF-8';

        while (true) {
            $page = $this->request('GET', '/admin/dummy/player', [
                'headers' => [
                    'accept' => 'application/json, text/javascript, */*; q=0.01',
                    'accept-encoding' => 'gzip, deflate, br',
                    'User-Agent'   => 'Mozilla/5.0 (Linux 3.4; rv:64.0) Gecko/20100101 Firefox/15.0',
                    'Cookie' => $this->cookie
                ],
                'query' => $queryParam
            ]);

            if ($page->getStatusCode() == 200) {
                $body = $page->getBody()->getContents();
                $body = json_decode($body, true);
            }

            if ($body['is_complete']) {
                $page = $this->request('GET', '/admin/dummy/player', [
                    'headers' => [
                        'accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.9',
                        'accept-encoding' => 'gzip, deflate, br',
                        'User-Agent'   => 'Mozilla/5.0 (Linux 3.4; rv:64.0) Gecko/20100101 Firefox/15.0',
                        'Cookie' => $this->cookie
                    ],
                    'decode_content' => false,
                    'query' => $queryParam . '&download=1',
                    'sink' => $path,
                    // 'debug' => true
                ]);
                if ($page->getStatusCode() == 200) {
                    return true;
                } else {
                    return false;
                }
                break;
            }
        }
        return false;
    }
}
