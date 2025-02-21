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

class ImportFromOlderVersion
{
    public const TYPE_CUSTOMER = 1;
    public const TYPE_EMBROIDERY = 2;
    public const TYPE_ORDER = 3;

    public static function getFieldsNote()
    {
        $fields = [
            '`id_mp_note`',
            '`type`',
            '`id_customer`',
            '`id_employee`',
            '`id_order`',
            '`note`',
            '`alert`',
            '`printable`',
            '`chat`',
            '`deleted`',
            '`date_add`',
            '`date_upd`',
        ];

        return $fields;
    }

    public static function getFieldsNoteAttachment()
    {
        $fields = [
            '`id_mp_note`',
            '`id_customer`',
            '`id_order`',
            '`type`',
            '`filename`',
            '`filetitle`',
            '`file_ext`',
            '`deleted`',
            '`date_add`',
            '`date_upd`',
        ];

        return $fields;
    }

    public static function importNoteCustomer($chunk)
    {
        $sql = 'INSERT IGNORE INTO `' . _DB_PREFIX_ . 'mp_note` (' . implode(',', self::getFieldsNote()) . ') VALUES ';

        foreach ($chunk as $row) {
            $values = [
                $row['id_customer_messages'],
                self::TYPE_CUSTOMER,
                $row['id_customer'],
                $row['id_employee'],
                0,
                pSQL($row['message']),
                1,
                0,
                0,
                0,
                $row['date_add'],
                $row['date_upd'],
            ];

            $map = array_map(function ($value) {
                return "'" . $value . "'";
            }, $values);

            $sql .= '(' . implode(', ', $map) . '),';
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

    public static function importNoteOrder($chunk)
    {
        $sql = 'INSERT IGNORE INTO `' . _DB_PREFIX_ . 'mp_note` (' . implode(',', self::getFieldsNote()) . ') VALUES ';

        foreach ($chunk as $row) {
            $values = [
                $row['id_mp_customer_order_notes'] + 200000,
                self::TYPE_ORDER,
                0,
                $row['id_employee'],
                $row['id_order'],
                pSQL($row['content']),
                1,
                $row['printable'],
                $row['chat'],
                $row['deleted'],
                $row['date_add'],
                $row['date_upd'],
            ];

            $map = array_map(function ($value) {
                return "'" . $value . "'";
            }, $values);

            $sql .= '(' . implode(', ', $map) . '),';
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

    public static function importNoteOrderAttachments($chunk)
    {
        $sql = 'INSERT IGNORE INTO `' . _DB_PREFIX_ . 'mp_note_attachment` (' . implode(',', self::getFieldsNoteAttachment()) . ') VALUES ';

        foreach ($chunk as $row) {
            $values = [
                $row['id_mp_customer_order_notes'] + 200000,
                $row['id_customer'] ?? 0,
                $row['id_order'] ?? 0,
                self::TYPE_ORDER,
                pSQL(substr ($row['filename'], 1)),
                pSQL($row['filetitle']),
                pSQL($row['file_ext']),
                0,
                $row['date_add'] ?? date('Y-m-d H:i:s'),
                $row['date_upd'] ?? date('Y-m-d H:i:s'),
            ];

            $map = array_map(function ($value) {
                return "'" . $value . "'";
            }, $values);

            $sql .= '(' . implode(', ', $map) . '),';
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

    public static function importNoteEmbroidery($chunk)
    {
        $sql = 'INSERT IGNORE INTO `' . _DB_PREFIX_ . 'mp_note` (' . implode(',', self::getFieldsNote()) . ') VALUES ';

        foreach ($chunk as $row) {
            $values = [
                $row['id_customer_archive'] + 400000,
                self::TYPE_EMBROIDERY,
                0,
                $row['id_employee'],
                $row['id_order'],
                pSQL($row['note']),
                1,
                $row['printable'],
                0,
                0,
                $row['date_add'],
                $row['date_upd'],
            ];

            $map = array_map(function ($value) {
                return "'" . $value . "'";
            }, $values);

            $sql .= '(' . implode(', ', $map) . '),';
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

    public static function importNoteEmbroideryAttachments($chunk)
    {
        $sql = 'INSERT IGNORE INTO `' . _DB_PREFIX_ . 'mp_note_attachment` (' . implode(',', self::getFieldsNoteAttachment()) . ') VALUES ';

        foreach ($chunk as $row) {
            $values = [
                $row['id_customer_archive'] + 400000,
                $row['id_customer'] ?? 0,
                $row['id_order'] ?? 0,
                self::TYPE_EMBROIDERY,
                pSQL($row['path']),
                pSQL($row['path']),
                pSQL($row['type']),
                0,
                $row['date_add'] ?? date('Y-m-d H:i:s'),
                $row['date_upd'] ?? date('Y-m-d H:i:s'),
            ];

            $map = array_map(function ($value) {
                return "'" . $value . "'";
            }, $values);

            $sql .= '(' . implode(', ', $map) . '),';
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
