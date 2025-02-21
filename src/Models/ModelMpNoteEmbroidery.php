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
class ModelMpNoteEmbroidery extends ObjectModel
{
    public const TYPE_INFORMATION = 1;
    public const TYPE_IMPORTANT = 2;
    public const TYPE_WARNING = 3;
    public $id_mp_note_embroidery;
    public $id_history;
    public $id_customer;
    public $id_employee;
    public $id_order;
    public $note;
    public $printable;
    public $date_add;
    public $date_upd;
    public $date_del;

    public static $definition = [
        'table' => 'mp_note_embroidery',
        'primary' => 'id_mp_note_embroidery',
        'fields' => [
            'id_history' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId'],
            'id_customer' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId'],
            'id_employee' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId'],
            'id_order' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId'],
            'note' => ['type' => self::TYPE_STRING, 'validate' => 'isCleanHtml', 'required' => true],
            'printable' => ['type' => self::TYPE_BOOL, 'validate' => 'isBool'],
            'date_add' => ['type' => self::TYPE_DATE, 'validate' => 'isDateFormat'],
            'date_upd' => ['type' => self::TYPE_DATE, 'validate' => 'isDateFormat', 'required' => false],
            'date_del' => ['type' => self::TYPE_DATE, 'validate' => 'isDateFormat', 'required' => false],
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
            ->where('a.id_customer = ' . (int) $id_order)
            ->where('a.id_mp_note_embroidery = ' . (int) $id_row);

        $result = Db::getInstance()->getRow($sql);

        if (!$result) {
            return [
                'id_mp_note_embroidery' => 0,
                'id_customer' => $id_order,
                'id_employee' => 0,
                'note' => '',
                'type' => self::TYPE_INFORMATION,
                'printable' => 0,
                'chat' => 0,
                'date_add' => date('Y-m-d H:i:s'),
                'date_upd' => date('Y-m-d H:i:s'),
                'employee' => $employee->firstname . ' ' . $employee->lastname,
                'attachments' => [],
            ];
        } else {
            $result['attachments'] = ModelMpNoteEmbroideryFile::getAttachments($result['id_mp_note_embroidery']);
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

        if ($rows) {
            foreach ($rows as &$row) {
                $row['attachments'] = ModelMpNoteEmbroideryFile::getAttachments($row['id_mp_note_embroidery']);
                $row['link'] = Context::getContext()->link->getAdminLink('AdminOrders', true, [], ['id_order' => $row['id_order'], 'vieworder' => 1]);
            }
        }

        $tpl = $context->smarty->createTemplate($module->getLocalPath() . 'views/templates/admin/partials/tbody/embroideryNote.tpl');
        $tpl->assign('note_list', $rows);

        return $tpl->fetch();
    }

    public static function getTBody($id_customer)
    {
        $context = Context::getContext();
        $module = Module::getInstanceByName('mpnotes');

        $sql = new DbQuery();
        $sql->select('a.*')
            ->select('count(b.id_mp_note_embroidery_file) as attachments')
            ->from('mp_note_embroidery', 'a')
            ->leftJoin('mp_note_embroidery_file', 'b', 'a.id_mp_note_embroidery = b.id_mp_note_embroidery')
            ->where('a.id_customer = ' . (int) $id_customer)
            ->groupBy('a.id_mp_note_embroidery')
            ->orderBy('a.date_add DESC');
        $sql = $sql->build();

        $rows = Db::getInstance()->executeS($sql);

        if ($rows) {
            foreach ($rows as &$row) {
                $row['link'] = Context::getCOntext()->link->getAdminLink('AdminOrders', true, [], ['id_order' => $row['id_order'], 'vieworder' => 1]);
            }
        }

        $tpl = $context->smarty->createTemplate($module->getLocalPath() . 'views/templates/admin/partials/tbody/embroideryNote.tpl');
        $tpl->assign('embroidery_notes', $rows);

        return $tpl->fetch();
    }
}
