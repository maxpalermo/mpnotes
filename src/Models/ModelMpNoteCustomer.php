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
class ModelMpNoteCustomer extends ObjectModel
{
    public const TYPE_INFORMATION = 1;
    public const TYPE_IMPORTANT = 2;
    public const TYPE_WARNING = 3;

    public $id_mp_note_customer;
    public $id_customer;
    public $id_employee;
    public $note;
    public $type;
    public $date_add;
    public $date_upd;

    public static $definition = [
        'table' => 'mp_note_customer',
        'primary' => 'id_mp_note_customer',
        'fields' => [
            'id_customer' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId'],
            'id_employee' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId'],
            'note' => ['type' => self::TYPE_STRING, 'validate' => 'isCleanHtml', 'required' => true],
            'type' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedInt'],
            'date_add' => ['type' => self::TYPE_DATE, 'validate' => 'isDateFormat'],
            'date_upd' => ['type' => self::TYPE_DATE, 'validate' => 'isDateFormat', 'required' => false],
        ],
    ];

    public static function getNote($id_customer, $id_row = 0)
    {
        $employee = Context::getcontext()->employee;

        $sql = new DbQuery();
        $sql->select('a.*')
            ->select('COALESCE(CONCAT(b.firstname, \' \', b.lastname), \'Sconosciuto\') AS employee ')
            ->from(self::$definition['table'], 'a')
            ->leftJoin('employee', 'b', 'a.id_employee = b.id_employee')
            ->where('a.id_customer = ' . (int) $id_customer)
            ->where('a.id_mp_note_customer = ' . (int) $id_row);

        $result = Db::getInstance()->getRow($sql);

        if (!$result) {
            return [
                'id_mp_note_customer' => 0,
                'id_customer' => $id_customer,
                'id_employee' => 0,
                'note' => '',
                'type' => self::TYPE_INFORMATION,
                'date_add' => date('Y-m-d H:i:s'),
                'date_upd' => date('Y-m-d H:i:s'),
                'employee' => $employee->firstname . ' ' . $employee->lastname,
            ];
        }

        return $result;
    }

    public static function getList($id_customer, $text)
    {
        $context = Context::getContext();
        $module = Module::getInstanceByName('mpnotes');

        $sql = new DbQuery();
        $sql->select('*')
            ->from(self::$definition['table'])
            ->where('`id_customer` = ' . (int) $id_customer)
            ->where('`note` LIKE \'%' . pSQL($text) . '%\'')
            ->orderBy('`date_add` DESC');
        $rows = Db::getInstance()->executeS($sql);

        $tpl = $context->smarty->createTemplate($module->getLocalPath() . 'views/templates/admin/partials/tbody/customerNote.tpl');
        $tpl->assign('note_list', $rows);

        return $tpl->fetch();
    }

    public static function getTBody($id_customer)
    {
        $context = Context::getContext();
        $module = Module::getInstanceByName('mpnotes');

        $sql = new DbQuery();
        $sql->select('*');
        $sql->from(self::$definition['table']);
        $sql->where('`id_customer` = ' . (int) $id_customer);
        $sql->orderBy('`date_add` DESC');
        $rows = Db::getInstance()->executeS($sql);

        $tpl = $context->smarty->createTemplate($module->getLocalPath() . 'views/templates/admin/partials/tbody/customerNote.tpl');
        $tpl->assign('customer_notes', $rows);

        return $tpl->fetch();
    }
}
