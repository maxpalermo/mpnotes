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

require_once _PS_MODULE_DIR_ . 'mpnotes/src/Models/ModelMpNoteOrderFile.php';

class ModelMpNoteOrder extends ObjectModel
{
    public const TYPE_INFORMATION = 1;
    public const TYPE_IMPORTANT = 2;
    public const TYPE_WARNING = 3;

    public $id_employee;
    public $id_order;
    public $note;
    public $deleted;
    public $printable;
    public $chat;
    public $date_add;
    public $date_upd;

    public static $definition = [
        'table' => 'mp_note_order',
        'primary' => 'id_mp_note_order',
        'fields' => [
            'id_employee' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId'],
            'id_order' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId'],
            'note' => ['type' => self::TYPE_STRING, 'validate' => 'isCleanHtml', 'required' => true],
            'deleted' => ['type' => self::TYPE_BOOL, 'validate' => 'isBool'],
            'printable' => ['type' => self::TYPE_BOOL, 'validate' => 'isBool'],
            'chat' => ['type' => self::TYPE_BOOL, 'validate' => 'isBool'],
            'date_add' => ['type' => self::TYPE_DATE, 'validate' => 'isDateFormat'],
            'date_upd' => ['type' => self::TYPE_DATE, 'validate' => 'isDateFormat', 'required' => false],
        ],
    ];

    public static function getNote($id_order, $id_row = 0)
    {
        $employee = Context::getcontext()->employee;

        $sql = new DbQuery();
        $sql->select('a.*')
            ->select('COALESCE(CONCAT(b.firstname, \' \', b.lastname), \'Sconosciuto\') AS employee ')
            ->from(self::$definition['table'], 'a')
            ->leftJoin('employee', 'b', 'a.id_employee = b.id_employee')
            ->where('a.id_order = ' . (int) $id_order)
            ->where('a.id_mp_note_order = ' . (int) $id_row);

        $result = Db::getInstance()->getRow($sql);

        if (!$result) {
            return [
                'id_mp_note_order' => 0,
                'id_order' => $id_order,
                'id_employee' => 0,
                'note' => '',
                'type' => self::TYPE_INFORMATION,
                'deleted' => 0,
                'printable' => 0,
                'chat' => 0,
                'date_add' => date('Y-m-d H:i:s'),
                'date_upd' => date('Y-m-d H:i:s'),
                'employee' => $employee->firstname . ' ' . $employee->lastname,
                'attachments' => [],
            ];
        } else {
            $result['attachments'] = ModelMpNoteOrderFile::getAttachments($result['id_mp_note_order']);
        }

        return $result;
    }

    public static function getList($id_order, $text)
    {
        $context = Context::getContext();
        $module = Module::getInstanceByName('mpnotes');

        $sql = new DbQuery();
        $sql->select('*')
            ->from(self::$definition['table'])
            ->where('`id_order` = ' . (int) $id_order)
            ->where('`note` LIKE \'%' . pSQL($text) . '%\'')
            ->orderBy('`date_add` DESC');
        $rows = Db::getInstance()->executeS($sql);

        if ($rows) {
            foreach ($rows as &$row) {
                $row['attachments'] = ModelMpNoteOrderFile::getAttachments($row['id_mp_note_order']);
            }
        }

        $tpl = $context->smarty->createTemplate($module->getLocalPath() . 'views/templates/admin/partials/tbody/orderNote.tpl');
        $tpl->assign('note_list', $rows);

        return $tpl->fetch();
    }

    public static function getTBody($id_order)
    {
        $context = Context::getContext();
        $module = Module::getInstanceByName('mpnotes');

        $sql = new DbQuery();
        $sql->select('*');
        $sql->from(self::$definition['table']);
        $sql->where('`id_order` = ' . (int) $id_order);
        $sql->orderBy('`date_add` DESC');
        $rows = Db::getInstance()->executeS($sql);

        $tpl = $context->smarty->createTemplate($module->getLocalPath() . 'views/templates/admin/partials/tbody/orderNote.tpl');
        $tpl->assign('order_notes', $rows);

        return $tpl->fetch();
    }
}
