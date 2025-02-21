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

namespace MpSoft\MpNotes\Fetch;

use MpSoft\MpNotes\Helpers\ImportFromOlderVersion;

class ModuleFetch
{
    private static $instance = null;

    private function __construct()
    {
        // Private constructor to prevent direct creation
    }

    private function __clone()
    {
        // Private clone method to prevent cloning
    }

    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    public function ajaxFetchCreateTable($params)
    {
        $response = ['success' => false, 'message' => ''];

        try {
            if (!isset($params['tableName']) || !$params['tableName']) {
                throw new \Exception('Parametro tabella mancante');
            }

            $table = $params['tableName'];
            $sql = [];

            switch ($table) {
                case 'mp_note_customer':
                    $sql[] = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'mp_note_customer` (
                        `id_mp_note_customer` int(11) NOT NULL AUTO_INCREMENT,
                        `id_customer` int(11) NOT NULL,
                        `id_employee` int(11) NOT NULL,
                        `note` text NOT NULL,
                        `date_add` datetime NOT NULL,
                        `date_upd` datetime NULL,
                        PRIMARY KEY (`id_mp_note_customer`),
                        KEY `id_customer` (`id_customer`)
                    ) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8;';

                    break;

                case 'mp_note_order':
                    $sql[] = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'mp_note_order` (
                        `id_mp_note_order` int(11) NOT NULL AUTO_INCREMENT,
                        `id_employee` int(11) NOT NULL,
                        `id_order` int(11) NOT NULL,
                        `note` text NOT NULL,
                        `deleted` tinyint(1) NOT NULL,
                        `printable` tinyint(1) NOT NULL,
                        `chat` tinyint(1) NOT NULL,
                        `date_add` datetime NOT NULL,
                        `date_upd` datetime NOT NULL,
                        PRIMARY KEY (`id_mp_note_order`),
                        KEY `id_order` (`id_order`)
                    ) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8;';

                    $sql[] = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'mp_note_order_file` (
                        `id_mp_note_order_file` int(11) NOT NULL AUTO_INCREMENT,
                        `id_mp_note_order` int(11) NOT NULL,
                        `id_order` int(11) NOT NULL,
                        `path` varchar(255) NOT NULL,
                        `filename` varchar(255) NOT NULL,
                        `filetitle` varchar(255) NOT NULL,
                        `file_ext` varchar(255) NOT NULL,
                        `date_add` datetime NOT NULL,
                        `date_upd` datetime NOT NULL,
                        PRIMARY KEY (`id_mp_note_order_file`),
                        KEY `id_mp_note_order` (`id_mp_note_order`)
                    ) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8;';

                    break;

                case 'mp_note_embroidery':
                    $sql[] = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'mp_note_embroidery` (
                        `id_mp_note_embroidery` int(11) NOT NULL AUTO_INCREMENT,
                        `id_history` int(11) NOT NULL,
                        `id_customer` int(11) NOT NULL,
                        `id_employee` int(11) NOT NULL,
                        `id_order` int(11) NOT NULL,
                        `note` text NOT NULL,
                        `printable` tinyint(1) NOT NULL,
                        `date_add` datetime NOT NULL,
                        `date_upd` datetime NULL,
                        `date_del` datetime NULL,
                        PRIMARY KEY (`id_mp_note_embroidery`),
                        KEY `id_order` (`id_order`)
                    ) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8;';

                    $sql[] = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'mp_note_embroidery_file` (
                        `id_mp_note_embroidery_file` int(11) NOT NULL AUTO_INCREMENT,
                        `id_mp_note_embroidery` int(11) NOT NULL,
                        `id_employee` int(11) NOT NULL,
                        `path` varchar(255) NOT NULL,
                        `type` varchar(16) NOT NULL,
                        `date_add` datetime NOT NULL,
                        `date_upd` datetime NULL,
                        PRIMARY KEY (`id_mp_note_embroidery_file`),
                        KEY `id_mp_note_embroidery` (`id_mp_note_embroidery`)
                    ) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8;';

                    break;

                default:
                    throw new \Exception('Tipo di tabella non valido');
            }

            foreach ($sql as $query) {
                if (!\Db::getInstance()->execute($query)) {
                    throw new \Exception("Errore durante la creazione della tabella {$table}");
                }
            }

            $response['success'] = true;
            $response['message'] = 'Tabella creata con successo';
        } catch (\Exception $e) {
            $response['message'] = $e->getMessage();
        }

        return $response;
    }

    public function ajaxFetchParseCSV($params)
    {
        $response = ['success' => false, 'message' => ''];
        $file = \Tools::fileAttachment('file', false);
        $file_att = \Tools::fileAttachment('file_att', false);

        if (!$file || $file['error']) {
            throw new \Exception('File non caricato');
        }

        if (!$file_att || $file_att['error']) {
            $file_att = false;
        }

        try {
            $reader = new \PhpOffice\PhpSpreadsheet\Reader\Csv();
            $encoding = \PhpOffice\PhpSpreadsheet\Reader\Csv::guessEncoding($file['tmp_name'], 'ISO-8859-1');
            $reader->setInputEncoding($encoding);
            $reader->setDelimiter(';');
            $reader->setEnclosure('');
            $reader->setSheetIndex(0);

            $spreadsheet = $reader->load($file['tmp_name']);

            $worksheet = $spreadsheet->getActiveSheet();
            $data = $worksheet->toArray();

            $headers = array_shift($data);
            $result = [];

            foreach ($data as $row) {
                $result[] = array_combine($headers, $row);
            }

            $response['success'] = true;
            $response['data'] = $result;
        } catch (\Exception $e) {
            $response['message'] = $e->getMessage();
        }

        if ($file_att) {
            try {
                $reader = new \PhpOffice\PhpSpreadsheet\Reader\Csv();
                $encoding = \PhpOffice\PhpSpreadsheet\Reader\Csv::guessEncoding($file_att['tmp_name'], 'ISO-8859-1');
                $reader->setInputEncoding($encoding);
                $reader->setDelimiter(';');
                $reader->setEnclosure('');
                $reader->setSheetIndex(0);

                $spreadsheet = $reader->load($file_att['tmp_name']);

                $worksheet = $spreadsheet->getActiveSheet();
                $data = $worksheet->toArray();

                $headers = array_shift($data);
                $result = [];

                foreach ($data as $row) {
                    $result[] = array_combine($headers, $row);
                }

                $response['success'] = true;
                $response['data_att'] = $result;
            } catch (\Exception $e) {
                $response['message'] = $e->getMessage();
            }
        } else {
            $response['success'] = true;
            $response['data_att'] = [];
        }

        return $response;
    }

    public function ajaxFetchTruncateTable($params)
    {
        $tableName = $params['tableName'];

        if (!$tableName) {
            return false;
        }

        \Db::getInstance()->execute('TRUNCATE TABLE `' . _DB_PREFIX_ . $tableName . '`');

        try {
            \Db::getInstance()->execute('TRUNCATE TABLE `' . _DB_PREFIX_ . $tableName . '_attachment`');
        } catch (\Throwable $th) {
            // niente
        }

        return [
            'success' => true,
            'message' => 'Tabella ' . $tableName . ' troncata con successo',
        ];
    }

    public function ajaxFetchImportCSV($params)
    {
        $chunk = json_decode($params['chunk'], true);
        $tablename = $params['type'];

        switch ($tablename) {
            case 'mp_note_customer':
                $result = ImportFromOlderVersion::importNoteCustomer($chunk);

                break;
            case 'mp_note_order':
                $result = ImportFromOlderVersion::importNoteOrder($chunk);

                break;
            case 'mp_note_order_attachment':
                $result = ImportFromOlderVersion::importNoteOrderAttachments($chunk);

                break;
            case 'mp_note_embroidery':
                $result = ImportFromOlderVersion::importNoteEmbroidery($chunk);

                break;
            case 'mp_note_embroidery_attachment':
                $result = ImportFromOlderVersion::importNoteEmbroideryAttachments($chunk);

                break;
            default:
                return [
                    'success' => false,
                    'message' => 'Tabella non valida',
                ];
        }

        return [
            'success' => $result,
            'message' => $result ? 'Dati importati con successo' : 'Errore durante l\'importazione',
        ];
    }

    protected function importNoteCustomer($chunk)
    {
        $sql = 'INSERT INTO `' . _DB_PREFIX_ . 'mp_note_customer` (`id_customer`, `id_employee`, `note`, `date_add`, `date_upd`) VALUES ';

        foreach ($chunk as $row) {
            $sql .= "('" . $row['id_customer'] . "', '" . $row['id_employee'] . "', '" . pSQL($row['message']) . "', '" . $row['date_add'] . "', '" . date('Y-m-d H:i:s') . "'),";
        }

        $sql = rtrim($sql, ',');
        $sql .= ';';

        try {
            $result = \Db::getInstance()->execute($sql);
        } catch (\Throwable $th) {
            return false;
        }

        return $result;
    }

    protected function importNoteOrder($chunk)
    {
        // Tabella principale
        $sql = 'INSERT INTO `' . _DB_PREFIX_ . 'mp_note_order` (`id_mp_note_order`,`id_employee`, `id_order`, `note`, `deleted`, `printable`, `chat`, `date_add`, `date_upd`) VALUES ';

        foreach ($chunk as $row) {
            $sql .= "('" . $row['id_mp_customer_order_notes'] . "', '" . $row['id_employee'] . "', '" . $row['id_order'] . "', '" . pSQL($row['content']) . "', '" . $row['deleted'] . "', '" . $row['printable'] . "', '" . $row['chat'] . "', '" . $row['date_add'] . "', '" . date('Y-m-d H:i:s') . "'),";
        }

        $sql = rtrim($sql, ',');
        $sql .= ';';

        try {
            $result = \Db::getInstance()->execute($sql);
        } catch (\Throwable $th) {
            return false;
        }

        return $result;
    }

    protected function importNoteOrderFile($chunk)
    {
        $sql = 'INSERT INTO `' . _DB_PREFIX_ . 'mp_note_order_file` (`id_mp_note_order`, `id_order`, `path`, `filename`, `filetitle`, `file_ext`, `date_add`, `date_upd`) VALUES ';
        foreach ($chunk as $row) {
            $sql .= "('" . $row['id_mp_customer_order_notes'] . "', '" . $row['id_order'] . "', '" . pSQL($row['link_path']) . "', '" . pSQL($row['filename']) . "', '" . pSQL($row['filetitle']) . "', '" . pSQL($row['file_ext']) . "', '" . date('Y-m-d H:i:s') . "', '" . date('Y-m-d H:i:s') . "'),";
        }

        $sql = rtrim($sql, ',');
        $sql .= ';';

        try {
            $result = \Db::getInstance()->execute($sql);
        } catch (\Throwable $th) {
            return false;
        }

        return $result;
    }

    protected function importNoteEmbroidery($chunk)
    {
        $sql = 'INSERT INTO `' . _DB_PREFIX_ . 'mp_note_embroidery` (`id_mp_note_embroidery`, `id_history`, `id_customer`, `id_employee`, `id_order`, `note`, `printable`, `date_add`, `date_upd`, `date_del`) VALUES ';

        foreach ($chunk as $row) {
            $sql .= "('" . $row['id_customer_archive'] . "', '" . $row['id_history'] . "', '" . $row['id_customer'] . "', '" . $row['id_employee'] . "', '" . $row['id_order'] . "', '" . pSQL($row['note']) . "', '" . $row['printable'] . "', '" . $row['date_add'] . "', '" . date('Y-m-d H:i:s') . "', '" . $row['date_del'] . "'),";
        }

        $sql = rtrim($sql, ',');
        $sql .= ';';

        try {
            $result = \Db::getInstance()->execute($sql);
        } catch (\Throwable $th) {
            return false;
        }

        return $result;
    }

    protected function importNoteEmbroideryFile($chunk)
    {
        $sql = 'INSERT INTO `' . _DB_PREFIX_ . 'mp_note_embroidery_file` (`id_mp_note_embroidery`, `id_employee`, `path`, `type`, `date_add`, `date_upd`) VALUES ';

        foreach ($chunk as $row) {
            $sql .= "('" . $row['id_customer_archive'] . "', '" . $row['id_employee'] . "', '" . pSQL($row['path']) . "', '" . pSQL($row['type']) . "', '" . $row['date_add'] . "', '" . date('Y-m-d H:i:s') . "'),";
        }

        $sql = rtrim($sql, ',');
        $sql .= ';';

        try {
            $result = \Db::getInstance()->execute($sql);
        } catch (\Throwable $th) {
            return false;
        }

        return $result;
    }
}
