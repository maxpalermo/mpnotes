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

use MpSoft\MpNotes\Models\ModelMpNote;
use MpSoft\MpNotes\Models\ModelMpNoteAttachment;

class ImportFromV16
{
    private $module;
    private $url;
    private $token;
    private $flash_message;
    private $flash_type;
    private $id_employee;
    private $errors;

    private const TYPE_CUSTOMER = 1;
    private const TYPE_ORDER = 2;
    private const TYPE_EMBROIDERY = 3;

    public function __construct($module)
    {
        $this->module = $module;
        $this->url = \Configuration::get('MP_REQUEST_API_URL');
        $this->token = \Configuration::get('MP_REQUEST_API_TOKEN');
        $this->id_employee = (int) \Context::getContext()->employee->id;
        $this->errors = [];
    }

    private function getTableNoteFields()
    {
        return [
            'id_history',
            'type',
            'reference',
            'id_customer',
            'id_order',
            'id_employee',
            'employee_firstname',
            'employee_lastname',
            'gravity',
            'content',
            'printable',
            'chat',
            'deleted',
            'date_add',
            'date_upd',
        ];
    }

    private function getTableNoteAttachmentFields()
    {
        return [
            'id_history',
            'type',
            'id_mpnote',
            'reference',
            'id_customer',
            'id_order',
            'id_employee',
            'employee_firstname',
            'employee_lastname',
            'filename',
            'filetitle',
            'file_ext',
            'deleted',
            'date_add',
            'date_upd'
        ];
    }

    public function truncate()
    {
        $db = \Db::getInstance();
        $db->execute('TRUNCATE TABLE `' . _DB_PREFIX_ . ModelMpNote::$definition['table'] . '`');
        $db->execute('TRUNCATE TABLE `' . _DB_PREFIX_ . ModelMpNoteAttachment::$definition['table'] . '`');

        $this->flash_message = $this->module->l('Truncate eseguito con successo');
        $this->flash_type = 'success';
    }

    public function getDataCustomerMessages($limit = 1000, $offset = 0)
    {
        $pfx = $this->getPrefix();

        $query = "
            SELECT
                0 as id_history,
                'customer' as type,
                null as reference,
                a.id_customer,
                0 as id_order,
                a.id_employee,
                upper(b.firstname) as employee_firstname,
                upper(b.lastname) as employee_lastname,
                'info' as gravity,
                a.message as content,
                0 as printable,
                0 as chat,
                0 as deleted,
                a.date_add,
                null as date_upd
            FROM
                `{$pfx}customer_messages` a
            LEFT JOIN
                `{$pfx}employee` b
                ON
                a.id_employee=b.id_employee
            ORDER BY
                date_add ASC, id_customer_messages ASC
            LIMIT 
                {$limit}
            OFFSET 
                {$offset}
            ";

        $data = $this->setQuery($query);

        return $data;
    }

    public function getDataOrderMessages($limit = 1000, $offset = 0, $attachments = 0)
    {
        $pfx = $this->getPrefix();

        if ($attachments) {
            return $this->getDataOrderMessagesAttachments($limit, $offset);
        }

        $query = "
            SELECT
                0 as id_history,
                'order' as type,
                a.id_mp_customer_order_notes as reference,
                0 as id_customer,
                a.id_order,
                a.id_employee,
                upper(b.firstname) as employee_firstname,
                upper(b.lastname) as employee_lastname,
                'info' as gravity,
                a.content,
                a.printable,
                a.chat,
                a.deleted,
                a.date_add,
                null as date_upd
            FROM
                `{$pfx}mp_customer_order_notes` a
            LEFT JOIN
                `{$pfx}employee` b
                ON
                a.id_employee=b.id_employee
            ORDER BY
                a.date_add ASC, a.id_mp_customer_order_notes ASC
            LIMIT 
                {$limit}
            OFFSET 
                {$offset}
            ";

        $data = $this->setQuery($query);

        return $data;
    }

    protected function getDataOrderMessagesAttachments($limit, $offset)
    {
        $pfx = $this->getPrefix();

        $query = "
            SELECT
                0 as id_history,
                'order' as type,
                null as id_mpnote,
                a.id_mp_customer_order_notes as reference,
                null as id_customer,
                a.id_order,
                0 as id_employee,
                '' as employee_firstname,
                '' as employee_lastname,
                a.filename,
                a.filetitle,
                a.file_ext,
                0 as deleted,
                NOW() as date_add,
                null as date_upd
            FROM
                `{$pfx}mp_customer_order_notes_attachments` a
            ORDER BY
                id_mp_customer_order_notes_attachments
            LIMIT 
                {$limit}
            OFFSET 
                {$offset}
            ";

        $data = $this->setQuery($query);

        return $data;
    }

    public function getDataEmbroideryMessages($limit = 1000, $offset = 0, $attachments = false)
    {
        $pfx = $this->getPrefix();

        if ($attachments) {
            return $this->getDataEmbroideryMessagesAttachments($limit, $offset);
        }

        $query = "
            SELECT
                a.id_history,
                'embroidery' as type,
                a.id_customer_archive as reference,
                a.id_customer,
                a.id_order,
                a.id_employee,
                upper(b.firstname) as employee_firstname,
                upper(b.lastname) as employee_lastname,
                'info' as gravity,
                a.note as content,
                a.printable,
                0 as `chat`,
                IF(a.date_del = '0000-00-00 00:00:00', 0, 1) as deleted,
                a.date_add,
                null as date_upd
            FROM
                `{$pfx}customer_archive` a
            LEFT JOIN
                `{$pfx}employee` b
                ON
                a.id_employee=b.id_employee
            ORDER BY
                a.date_add ASC, a.id_customer_archive ASC
            LIMIT 
                {$limit}
            OFFSET 
                {$offset}
            ";

        $data = $this->setQuery($query);

        return $data;
    }

    protected function getDataEmbroideryMessagesAttachments($limit, $offset)
    {
        $pfx = $this->getPrefix();

        $query = "
            SELECT
                0 as id_history,
                'embroidery' as type,
                null as id_mpnote,
                a.id_customer_archive as reference,
                null as id_customer,
                null as id_order,
                0 as id_employee,
                '' as employee_firstname,
                '' as employee_lastname,
                a.path as filename,
                a.path as filetitle,
                a.type as file_ext,
                0 as deleted,
                a.date_add,
                null as date_upd
            FROM
                `{$pfx}customer_archive_item` a
            ORDER BY
                id_customer_archive_item
            LIMIT 
                {$limit}
            OFFSET 
                {$offset}
            ";

        $data = $this->setQuery($query);

        return $data;
    }

    public function getCountRows($tablename)
    {
        $pfx = $this->getPrefix();
        $tablename = "{$pfx}{$tablename}";

        $query = "SELECT COUNT(*) as total_rows FROM {$tablename}";

        $data = $this->setQuery($query);

        if ($data) {
            return (int) $data[0]['total_rows'];
        }
    }

    protected function getPrefix()
    {
        $pfx = \Configuration::get('MP_REQUEST_API_DB_PREFIX');
        if (!$pfx) {
            $pfx = _DB_PREFIX_;
        }

        return $pfx;
    }

    public function setQuery($query)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_ENCODING, '');
        curl_setopt($ch, CURLOPT_MAXREDIRS, 10);
        curl_setopt($ch, CURLOPT_TIMEOUT, 0);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($ch, CURLOPT_POSTFIELDS, 'token=' . $this->token . '&query=' . $query);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/x-www-form-urlencoded'
        ]);

        $response = curl_exec($ch);

        curl_close($ch);

        if ($response === false) {
            $this->flash_message = $this->module->l('Errore nella richiesta');
            $this->flash_type = 'danger';
            return false;
        }

        $this->flash_message = $this->module->l('Richiesta effettuata con successo');
        $this->flash_type = 'success';

        $data = json_decode($response, true);
        if (!$data['success']) {
            $this->flash_message = $this->module->l('Errore nella richiesta');
            $this->flash_type = 'danger';

            return false;
        }

        // Decodifico i dati base64
        $base64 = base64_decode($data['data']);
        // Decodifico i dati json
        $data = json_decode($base64, true);

        return $data;
    }

    public function doImport($dataJson, $attachments = false)
    {
        $pfx = _DB_PREFIX_;
        $table = ModelMpNote::$definition['table'];
        if ($attachments) {
            $table .= '_attachment';
        }
        $db = \Db::getInstance();
        // Controllo se il dato è di tipo json o array

        if (!is_array($dataJson)) {
            $data = json_decode($dataJson, true);
        } else {
            $data = $dataJson;
        }

        if (!$data) {
            $this->flash_message = $this->module->l('Nessun dato da importare');
            $this->flash_type = 'warning';

            return true;
        }

        if (isset($data['success']) && $data['success'] == 0) {
            $this->flash_message = $data['message'];
            $this->flash_type = 'danger';

            return false;
        }

        if (!$attachments) {
            $INSERT = "
                INSERT INTO {$pfx}{$table}
                    (id_history, type, reference, id_customer, id_order, id_employee, employee_firstname, employee_lastname, gravity, content, printable, chat, deleted, date_add, date_upd)
                VALUES
            ";
        } else {
            $INSERT = "
                INSERT INTO {$pfx}{$table}
                    (id_history, type, id_mpnote, reference, id_customer, id_order, id_employee, employee_firstname, employee_lastname, filename, filetitle, file_ext, deleted, date_add, date_upd)
                VALUES
            ";
        }
        $VALUES = [];
        foreach ($data as &$row) {
            $rowInsert = '(' . implode(',', array_map(function ($value) {
                $value = pSQL($value);
                if ($value === null) {
                    return 'NULL';
                }
                return "'{$value}'";
            }, $row)) . ')';
            $VALUES[] = $rowInsert;
        }

        $INSERT .= implode(',', $VALUES) . ';';
        try {
            $db->execute($INSERT);
        } catch (\Throwable $th) {
            $this->errors[] = "Errore durante l'inserimento del record: {$row['id_customer']}: {$th->getMessage()};\n{$INSERT}\n";
        }

        return $this->errors;
    }
}
