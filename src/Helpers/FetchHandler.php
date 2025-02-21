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
    protected $className;
    protected $params;

    public function __construct($className)
    {
        $this->className = $className;
        $httpRequest = \Tools::getAllValues();
        $jsonRequest = json_decode(file_get_contents('php://input'), true);

        if (is_array($httpRequest) && is_array($jsonRequest)) {
            $params = array_merge($httpRequest, $jsonRequest);
        } elseif (is_array($httpRequest)) {
            $params = $httpRequest;
        } elseif (is_array($jsonRequest)) {
            $params = $jsonRequest;
        } else {
            $params = [];
        }

        if ($params && isset($params['hasFile']) && $params['hasFile']) {
            $params['file'] = \Tools::fileAttachment('file', false);
        }

        $this->params = $params;
        if (isset($params['ajax']) && $params['ajax']) {
            $this->processData($params);
        }
    }

    protected function parseMultipartFormData($rawData)
    {
        $data = [];
        $boundary = substr($rawData, 0, strpos($rawData, "\r\n"));
        $parts = array_slice(explode($boundary, $rawData), 1);

        foreach ($parts as $part) {
            if ($part == "--\r\n") {
                break;
            } // End of data

            $part = trim($part);
            if (empty($part)) {
                continue;
            }

            list($rawHeaders, $body) = explode("\r\n\r\n", $part, 2);
            $rawHeaders = explode("\r\n", $rawHeaders);
            $headers = [];
            foreach ($rawHeaders as $header) {
                list($name, $value) = explode(':', $header);
                $headers[strtolower(trim($name))] = trim($value);
            }

            // Parse Content-Disposition to get the name
            if (isset($headers['content-disposition'])) {
                $headerParts = explode(';', $headers['content-disposition']);
                foreach ($headerParts as $part) {
                    if (strpos($part, 'name=') !== false) {
                        $name = trim(str_replace(['name=', '"'], '', $part));
                        $data[$name] = substr($body, 0, strlen($body)); // Remove trailing \r\n

                        continue;
                    }
                    if (strpos($part, 'filename=') !== false) {
                        $name = trim(str_replace(['filename=', '"'], '', $part));
                        $data[$name] = substr($body, 0, strlen($body)); // Remove trailing \r\n

                        continue;
                    }
                }
            }
        }

        return $data;
    }

    protected function processData($params)
    {
        if (isset($params['action'])) {
            $action = 'ajaxFetch' . ucfirst($params['action']);
            if (method_exists($this->className, $action)) {
                Response::json($this->className::getInstance()->$action($params));
            }
        }
    }
}
