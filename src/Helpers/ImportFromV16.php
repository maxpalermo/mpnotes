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
    private $id_employee;
    private $errors = [];

    public function __construct($module)
    {
        $this->module = $module;
        $this->id_employee = (int) \Context::getContext()->employee->id;
    }

    public function importCustomerArchive($list)
    {
        $imported = 0;
        foreach ($list as $item) {
            $model = new ModelMpNote();
            $model->hydrate($item);
            try {
                $model->add();
                $imported++;
            } catch (\Throwable $th) {
                $this->errors[] = [
                    'item' => $item,
                    'error' => $th->getMessage()
                ];
            }
        }

        return [
            'imported' => $imported,
            'errors' => $this->errors
        ];
    }

    public function importCustomerArchiveItem($list)
    {
        $imported = 0;
        foreach ($list as $item) {
            $model = new ModelMpNoteAttachment();
            $model->hydrate($item);
            try {
                $model->add();
                $imported++;
                $item['id'] = $model->id;
                $this->updateCustomerArchiveItem($item);
            } catch (\Throwable $th) {
                $this->errors[] = [
                    'item' => $item,
                    'error' => $th->getMessage()
                ];
            }
        }

        return [
            'imported' => $imported,
            'errors' => $this->errors
        ];
    }

    public function importCustomerMessages($list)
    {
        $imported = 0;
        foreach ($list as $item) {
            $model = new ModelMpNote();
            $model->hydrate($item);
            try {
                $model->add();
                $imported++;
            } catch (\Throwable $th) {
                $this->errors[] = [
                    'item' => $item,
                    'error' => $th->getMessage()
                ];
            }
        }

        return [
            'imported' => $imported,
            'errors' => $this->errors
        ];
    }

    public function updateCustomerArchiveItem($item)
    {
        $db = \Db::getInstance();
        $table = _DB_PREFIX_ . 'mpnote';
        $table_att = _DB_PREFIX_ . 'mpnote_attachment';
        $sql = "select id_mpnote from {$table} where id_import={$item['id_parent']} and reference='customer_archive'";
        $id_mpnote = $db->getValue($sql);

        if (!$id_mpnote) {
            $sql = "select id_mpnote from {$table} where id_parent={$item['id_parent']} and reference='customer_archive'";
            $id_mpnote = $db->getValue($sql);
        }

        if (!$id_mpnote) {
            return 0;
        }

        $sql = "update {$table_att} set id_mpnote={$id_mpnote} where id_mpnote_attachment={$item['id']}";

        return $db->execute($sql);
    }

    public function importCustomerPrivateNotes($list)
    {
        $imported = 0;
        foreach ($list as $item) {
            $model = new ModelMpNote();
            $model->hydrate($item);
            try {
                $model->add();
                $imported++;
            } catch (\Throwable $th) {
                $this->errors[] = [
                    'item' => $item,
                    'error' => $th->getMessage()
                ];
            }
        }

        return [
            'imported' => $imported,
            'errors' => $this->errors
        ];
    }

    public function importMpCustomerOrderNotes($list)
    {
        $imported = 0;
        foreach ($list as $item) {
            $model = new ModelMpNote();
            $model->hydrate($item);
            try {
                $model->add();
                $imported++;
            } catch (\Throwable $th) {
                $this->errors[] = [
                    'item' => $item,
                    'error' => $th->getMessage()
                ];
            }
        }

        return [
            'imported' => $imported,
            'errors' => $this->errors
        ];
    }

    public function importMpCustomerOrderNotesAttachments($list)
    {
        $imported = 0;
        foreach ($list as $item) {
            $model = new ModelMpNoteAttachment();
            $model->hydrate($item);
            try {
                $model->add();
                $imported++;
                $item['id'] = $model->id;
                $this->updateMpCustomerOrderNotesAttachments($item);
            } catch (\Throwable $th) {
                $this->errors[] = [
                    'item' => $item,
                    'error' => $th->getMessage()
                ];
            }
        }

        return [
            'imported' => $imported,
            'errors' => $this->errors
        ];
    }

    public function updateMpCustomerOrderNotesAttachments($item)
    {
        $db = \Db::getInstance();
        $table = _DB_PREFIX_ . 'mpnote';
        $table_att = _DB_PREFIX_ . 'mpnote_attachment';
        $sql = "select
                id_mpnote,
                id_employee,
                id_customer,
                customer_firstname,
                customer_lastname,
                employee_firstname,
                employee_lastname
            from
                {$table}
            where
                id_import={$item['id_parent']} and reference='mp_customer_order_notes'";
        $mpnote = $db->getRow($sql);

        if (!$mpnote) {
            $sql = "select id_mpnote from {$table} where id_parent={$item['id_parent']} and reference='mp_customer_order_notes'";
            $mpnote = $db->getRow($sql);
        }

        if (!$mpnote) {
            return 0;
        }

        $sql = "
            update
                {$table_att}
            set
                id_mpnote={$mpnote['id_mpnote']},
                id_employee={$mpnote['id_employee']},
                id_customer={$mpnote['id_customer']},
                customer_firstname='{$mpnote['customer_firstname']}',
                customer_lastname='{$mpnote['customer_lastname']}',
                employee_firstname='{$mpnote['employee_firstname']}',
                employee_lastname='{$mpnote['employee_lastname']}'
            where id_mpnote_attachment={$item['id']}";

        return $db->execute($sql);
    }

    public function getErrors()
    {
        return $this->errors;
    }
}
