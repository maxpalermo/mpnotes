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

class CurlExec
{
    public static function exec($endpoint, $action, $query, $token, $type, $table, $verifySsl = true, $debug = false)
    {
        if (!$endpoint) {
            return [
                'success' => false,
                'message' => 'Endpoint non valido',
                'type' => $type,
            ];
        }

        if (!function_exists('curl_init')) {
            return [
                'success' => false,
                'message' => 'Estensione PHP cURL non disponibile',
                'type' => $type,
            ];
        }

        $payload = json_encode([
            'action' => $action,
            'query' => $query,
        ]);

        if ($payload === false) {
            return [
                'success' => false,
                'message' => 'Impossibile serializzare payload JSON',
                'type' => $type,
            ];
        }

        $ch = curl_init($endpoint);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_MAXREDIRS, 5);
        curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
        curl_setopt($ch, CURLOPT_ENCODING, '');
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, (bool) $verifySsl);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, $verifySsl ? 2 : 0);

        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Accept: application/json',
            'Content-Type: application/json; charset=utf-8',
            'X-AUTH-TOKEN: ' . $token,
            'Expect:',
        ]);

        curl_setopt($ch, CURLOPT_USERAGENT, 'mpnotes/1.0 (PHP cURL)');
        curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);

        $verbose = '';
        $verboseStream = null;
        if ($debug) {
            $verboseStream = fopen('php://temp', 'w+');
            curl_setopt($ch, CURLOPT_VERBOSE, true);
            curl_setopt($ch, CURLOPT_STDERR, $verboseStream);
        }

        $raw = curl_exec($ch);
        $curlErrNo = curl_errno($ch);
        $curlErr = curl_error($ch);
        $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $info = curl_getinfo($ch);

        if ($verboseStream) {
            rewind($verboseStream);
            $verbose = (string) stream_get_contents($verboseStream);
            fclose($verboseStream);
        }

        curl_close($ch);

        if ($curlErrNo || $raw === false) {
            return [
                'success' => false,
                'message' => 'Errore cURL: ' . ($curlErr ?: 'curl_exec returned false'),
                'errno' => $curlErrNo,
                'httpCode' => $httpCode,
                'curlError' => $curlErr,
                'curlInfo' => $info,
                'curlVerbose' => $verbose,
                'rawBody' => $raw,
                'type' => $type,
                'recordCount' => static::getRecordCount($table),
                'request' => [
                    'endpoint' => $endpoint,
                    'action' => $action,
                    'query' => $query,
                ],
            ];
        }

        $decoded = json_decode($raw, true);
        if ($decoded === null && json_last_error() !== JSON_ERROR_NONE) {
            return [
                'success' => false,
                'message' => 'Risposta non JSON: ' . json_last_error_msg(),
                'httpCode' => $httpCode,
                'rawBody' => $raw,
                'type' => $type,
                'recordCount' => static::getRecordCount($table),
                'request' => [
                    'endpoint' => $endpoint,
                    'action' => $action,
                    'query' => $query,
                ],
                'curlInfo' => $info,
                'curlVerbose' => $verbose,
            ];
        }

        $transportSuccess = $httpCode >= 200 && $httpCode < 300;

        return [
            'success' => $transportSuccess,
            'httpCode' => $httpCode,
            'remote' => $decoded,
            'rawBody' => $raw,
            'type' => $type,
            'recordCount' => static::getRecordCount($table),
            'request' => [
                'endpoint' => $endpoint,
                'action' => $action,
                'query' => $query,
            ],
            'curlInfo' => $info,
            'curlVerbose' => $verbose,
        ];
    }

    public static function getRecordCount($table)
    {
        if (!$table) {
            return '--';
        }

        if (preg_match('/^#/', $table)) {
            $reference = substr($table, 1);
            $table = 'mpnote';
        } elseif (preg_match('/^@/', $table)) {
            $reference = substr($table, 1);
            $table = 'mpnote_attachment';
        } else {
            return '--';
        }

        $db = \Db::getInstance();
        $table = _DB_PREFIX_ . $table;
        $query = "select count(*) from {$table} where `reference` like '{$reference}'";

        try {
            $result = $db->getValue($query);
        } catch (\Throwable $th) {
            return '--';
        }

        return $result;
    }

    public static function getCustomerArchiveRecords($endpoint, $token, $limit = 5000, $offset = 0)
    {
        $query = "SELECT 
                a.id_customer_archive as `id_import`,
                a.id_history as `id_parent`,
                'embroidery' as `type`,
                'customer_archive' as `reference`,
                a.id_customer,
                a.id_order as `id_order`,
                a.id_employee,
                c.firstname as `customer_firstname`,
                c.lastname as `customer_lastname`,
                e.firstname as `employee_firstname`,
                e.lastname as `employee_lastname`,
                null as `gravity`,
                a.note as `content`,
                a.printable,
                null as `chat`,
                IF(date_del IS NULL, 0, 1) AS `deleted`,
                a.date_add,
                a.date_upd
                FROM ps_customer_archive a
                LEFT JOIN ps_customer c ON a.id_customer = c.id_customer
                LEFT JOIN ps_employee e ON a.id_employee = e.id_employee
                WHERE a.id_customer_archive NOT IN (
                    SELECT DISTINCT id_history
                    FROM ps_customer_archive
                    WHERE id_history != 0
                )
                ORDER BY a.id_customer_archive
                LIMIT {$limit}
                OFFSET {$offset}
        ";

        $query = trim(str_replace("\n", ' ', $query));

        return static::exec($endpoint, 'setQuery', $query, $token, 'embroidery', '#customer_archive');
    }

    public static function getCustomerArchiveItemRecords($endpoint, $token, $limit = 5000, $offset = 0)
    {
        $query = "SELECT 
                a.id_customer_archive_item as `id_import`,
                a.id_customer_archive as `id_parent`,
                0 as `id_mpnote`,
                null as id_customer,
                null as `id_order`,
                a.id_employee,
                'embroidery' as `type`,
                'customer_archive_item' as `reference`,
                null as `customer_firstname`,
                null as `customer_lastname`,
                e.firstname as `employee_firstname`,
                e.lastname as `employee_lastname`,
                a.path as `filename`,
                a.path as `filetitle`,
                a.type as `file_ext`,
                0 as `deleted`,
                a.date_add,
                a.date_upd
                FROM ps_customer_archive_item a
                LEFT JOIN ps_employee e ON a.id_employee = e.id_employee
                ORDER BY a.id_customer_archive_item
                LIMIT {$limit}
                OFFSET {$offset}
        ";

        $query = trim(str_replace("\n", ' ', $query));

        return static::exec($endpoint, 'setQuery', $query, $token, 'embroidery', '@customer_archive_item');
    }

    public static function getCustomerMessagesRecords($endpoint, $token, $limit = 5000, $offset = 0)
    {
        $query = "SELECT 
                a.id_customer_messages as `id_import`,
                0 as `id_parent`,
                'customer' as `type`,
                'customer_messages' as `reference`,
                a.id_customer,
                null as `id_order`,
                a.id_employee,
                c.firstname as `customer_firstname`,
                c.lastname as `customer_lastname`,
                e.firstname as `employee_firstname`,
                e.lastname as `employee_lastname`,
                null as `gravity`,
                a.message as `content`,
                null as `printable`,
                null as `chat`,
                0 as `deleted`,
                a.date_add,
                null as `date_upd`
                FROM ps_customer_messages a
                LEFT JOIN ps_customer c ON a.id_customer = c.id_customer
                LEFT JOIN ps_employee e ON a.id_employee = e.id_employee
                ORDER BY a.id_customer_messages
                LIMIT {$limit}
                OFFSET {$offset}
        ";

        $query = trim(str_replace("\n", ' ', $query));

        return static::exec($endpoint, 'setQuery', $query, $token, 'customer', '#customer_messages');
    }

    public static function getCustomerPrivateNoteRecords($endpoint, $token, $limit = 5000, $offset = 0)
    {
        $query = "SELECT 
                a.id_customer as `id_import`,
                0 as `id_parent`,
                'customer' as `type`,
                'customer' as `reference`,
                a.id_customer,
                null as `id_order`,
                null as `id_employee`,
                c.firstname as `customer_firstname`,
                c.lastname as `customer_lastname`,
                null as `employee_firstname`,
                null as `employee_lastname`,
                null as `gravity`,
                a.note as `content`,
                null as `printable`,
                null as `chat`,
                0 as `deleted`,
                a.date_add,
                null as `date_upd`
                FROM ps_customer a
                LEFT JOIN ps_customer c ON a.id_customer = c.id_customer
                WHERE a.note IS NOT NULL
                ORDER BY a.id_customer
                LIMIT {$limit}
                OFFSET {$offset}
        ";

        $query = trim(str_replace("\n", ' ', $query));

        return static::exec($endpoint, 'setQuery', $query, $token, 'customer_message', '#customer');
    }

    public static function getCustomerOrderNotesRecords($endpoint, $token, $limit = 5000, $offset = 0)
    {
        $query = "SELECT 
                a.id_mp_customer_order_notes as `id_import`,
                null as `id_parent`,
                'order' as `type`,
                'mp_customer_order_notes' as `reference`,
                null as `id_customer`,
                a.id_order as `id_order`,
                a.id_employee,
                null as `customer_firstname`,
                null as `customer_lastname`,
                e.firstname as `employee_firstname`,
                e.lastname as `employee_lastname`,
                null as `gravity`,
                a.content as `content`,
                a.printable,
                a.chat,
                a.deleted,
                a.date_add,
                null as `date_upd`
                FROM ps_mp_customer_order_notes a
                LEFT JOIN ps_employee e ON a.id_employee = e.id_employee
                ORDER BY a.id_mp_customer_order_notes
                LIMIT {$limit}
                OFFSET {$offset}
        ";

        $query = trim(str_replace("\n", ' ', $query));

        return static::exec($endpoint, 'setQuery', $query, $token, 'order', '#mp_customer_order_notes');
    }

    public static function getCustomerOrderNotesAttachmentsRecords($endpoint, $token, $limit = 5000, $offset = 0)
    {
        $query = "SELECT 
                a.id_mp_customer_order_notes_attachments as `id_import`,
                a.id_mp_customer_order_notes as `id_parent`,
                0 as `id_mpnote`,
                null as id_customer,
                a.id_order,
                null as `id_employee`,
                'order' as `type`,
                'mp_customer_order_notes_attachments' as `reference`,
                null as `customer_firstname`,
                null as `customer_lastname`,
                null as `employee_firstname`,
                null as `employee_lastname`,
                a.filename,
                a.filetitle,
                a.file_ext,
                0 as `deleted`,
                null as `date_add`,
                null as `date_upd`
                FROM ps_mp_customer_order_notes_attachments a
                ORDER BY a.id_mp_customer_order_notes_attachments
                LIMIT {$limit}
                OFFSET {$offset}
        ";

        $query = trim(str_replace("\n", ' ', $query));

        return static::exec($endpoint, 'setQuery', $query, $token, 'order', '@mp_customer_order_notes_attachments');
    }
}
