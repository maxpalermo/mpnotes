<?php
/**
 * Copyright since 2007 PrestaShop SA and Contributors
 * PrestaShop is an International Registered Trademark & Property of PrestaShop SA
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License version 3.0
 * that is bundled with this package in the file LICENSE.md.
 * It is also available through the world-wide-web at this URL:
 * https://opensource.org/licenses/AFL-3.0
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@prestashop.com so we can send you a copy immediately.
 *
 * @author    Massimiliano Palermo <maxx.palermo@gmail.com>
 * @copyright Since 2016 Massimiliano Palermo
 * @license   https://opensource.org/licenses/AFL-3.0 Academic Free License version 3.0
 */

namespace MpSoft\MpNotes\Helpers;

class FetchHandler
{
    protected $controller;
    protected $module;
    protected $phpData;

    public function __construct(\Module $module, \ModuleFrontController $controller)
    {
        $this->module = $module;
        $this->controller = $controller;

        $jsonPhpData = file_get_contents('php://input');
        if (!$jsonPhpData) {
            $this->phpData = \Tools::getAllValues();
        } elseif ($jsonPhpData && !JsonDecoder::isJson($jsonPhpData)) {
            // trasformo l'url query in array
            $split = explode('&', $jsonPhpData);
            $this->phpData = [];
            foreach ($split as $item) {
                $pair = explode('=', $item);
                $this->phpData[$pair[0]] = urldecode($pair[1]);
            }
        } else {
            $this->phpData = JsonDecoder::decodeJson($jsonPhpData, []);
        }
    }

    public function run()
    {
        if ($this->phpData && isset($this->phpData['action']) && $this->phpData['action']) {
            //$action = 'processAjax' . \Tools::ucfirst($this->phpData['action']);
            $action = $this->phpData['action'];
            if (method_exists($this->controller, $action)) {
                $result = $this->controller->$action();

                $this->jsonOutput($this->secureResponse($result));
            }
        }
    }

    public function getPhpData()
    {
        return $this->phpData;
    }

    protected function secureResponse($response)
    {
        $ps_protocol = \Configuration::get('PS_SSL_ENABLED' ) ? 'https' : 'http';
        $ps_domain = \Configuration::get('PS_SHOP_DOMAIN');
        $allowedOrigins = [
            $ps_protocol . '://' . $ps_domain,
        ];

        $origin = $_SERVER['HTTP_ORIGIN'] ?? '';

        if (in_array($origin, $allowedOrigins)) {
            header("Access-Control-Allow-Origin: $origin");
        } else {
            header('HTTP/1.1 403 Accesso negato');
            exit;
        }

        // Gestione preflight OPTIONS
        if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
            exit(0);
        }

        header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
        header('Access-Control-Allow-Credentials: true');
        header('Content-Type: application/json');
        header('Cache-Control: no-cache, no-store, must-revalidate');
        header('Pragma: no-cache');
        header('Expires: 0');
        header('X-Robots-Tag: noindex, nofollow');
        header('X-Content-Type-Options: nosniff');
        header('X-XSS-Protection: 1; mode=block');
        header('X-Frame-Options: DENY');

        return $response;
    }

    protected function jsonOutput($data)
    {
        exit(json_encode($data));
    }
}
