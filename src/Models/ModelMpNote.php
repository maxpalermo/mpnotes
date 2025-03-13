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
class ModelMpNote extends ObjectModel
{
    public const TYPE_NOTE_CUSTOMER = 1;
    public const TYPE_NOTE_EMBROIDERY = 2;
    public const TYPE_NOTE_ORDER = 3;
    public const TYPE_ALERT_INFORMATION = 1;
    public const TYPE_ALERT_IMPORTANT = 2;
    public const TYPE_ALERT_WARNING = 3;
    public const TYPE_ALERT_DANGER = 4;

    public $type; // Tipo di messaggio (vedi TYPE_NOTE)
    public $id_customer;
    public $id_employee;
    public $id_order;
    public $note;
    public $alert; // Tipo di avviso (vedi TYPE_ALERT)
    public $printable;
    public $chat;
    public $deleted;
    public $date_add;
    public $date_upd;

    public static $definition = [
        'table' => 'mp_note',
        'primary' => 'id_mp_note',
        'fields' => [
            'type' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedInt'],
            'id_customer' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId'],
            'id_employee' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId'],
            'id_order' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'default' => 0],
            'note' => ['type' => self::TYPE_STRING, 'validate' => 'isCleanHtml', 'required' => true],
            'alert' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedInt'],
            'printable' => ['type' => self::TYPE_BOOL, 'validate' => 'isBool', 'required' => false, 'default' => 0],
            'chat' => ['type' => self::TYPE_BOOL, 'validate' => 'isBool', 'required' => false, 'default' => 0],
            'deleted' => ['type' => self::TYPE_BOOL, 'validate' => 'isBool', 'required' => false, 'default' => 0],
            'date_add' => ['type' => self::TYPE_DATE, 'validate' => 'isDateFormat'],
            'date_upd' => ['type' => self::TYPE_DATE, 'validate' => 'isDateFormat', 'required' => false],
        ],
    ];

    /**
     * Retrieves the specified note
     *
     * @param int $type specifies the type of the note
     * @param int $id_customer specifies the id of the customer
     * @param int $id_order specifies the id of the order [optional]
     * @param int $id_row specifies the id of the note [optional]
     *
     * @return array returns the note in array
     */
    public static function getNote($noteTypeId, $id_customer, $id_order = 0, $id_row = 0, $isNew = false)
    {
        $employee = Context::getcontext()->employee;

        $sql = new DbQuery();

        $sql->select('a.*')
            ->select('COALESCE(CONCAT(b.firstname, \' \', b.lastname), \'Sconosciuto\') AS employee ')
            ->from(self::$definition['table'], 'a')
            ->leftJoin('employee', 'b', 'a.id_employee = b.id_employee');

        if ($id_row) {
            $sql->where('a.id_mp_note = ' . (int) $id_row);
        } elseif ($noteTypeId == self::TYPE_NOTE_EMBROIDERY) {
            $sql->where('a.type = ' . (int) $noteTypeId);
            $sql->where('a.id_customer = ' . (int) $id_customer);
        } elseif ($noteTypeId == self::TYPE_NOTE_ORDER) {
            $sql->where('a.type = ' . (int) $noteTypeId);
            $sql->where('a.id_order = ' . (int) $id_order);
        } elseif ($noteTypeId == self::TYPE_NOTE_CUSTOMER) {
            $sql->where('a.type = ' . (int) $noteTypeId);
            $sql->where('a.id_customer = ' . (int) $id_customer);
        }

        if ($isNew) {
            $sql->where('1=0');
        }

        $result = Db::getInstance()->getRow($sql);

        $noteTypes = [
            "Sconosciuto",
            "Nota Cliente",
            "Nota Ricamo",
            "Nota Ordine"
        ];

        if (!$result) {
            return [
                'id_mp_note' => 0,
                'type' => $noteTypeId,
                'id_customer' => $id_customer,
                'id_employee' => (int) Context::getcontext()->employee->id,
                'id_order' => $id_order,
                'note' => '',
                'alert' => self::TYPE_ALERT_INFORMATION,
                'printable' => 0,
                'chat' => 0,
                'deleted' => 0,
                'date_add' => date('Y-m-d H:i:s'),
                'date_upd' => date('Y-m-d H:i:s'),
                'employee' => $employee->firstname . ' ' . $employee->lastname,
                'attachments' => [],
                'title' => $noteTypes[$noteTypeId],
            ];
        } else {
            $result['title'] = $noteTypes[$noteTypeId];
            $result['attachments'] = ModelMpNoteAttachment::getAttachments($result['id_mp_note']);
        }

        return $result;
    }

    /**
     * Retrieves the count of notes for a customer, an order
     *
     * @param int $type
     * @param int $id_customer
     * @param int $id_order
     *
     * @return int
     */
    public static function getNoteCount($type, $id_customer = 0, $id_order = 0)
    {
        $sql = new DbQuery();
        $sql->select('COUNT(*)')
            ->from(self::$definition['table'], 'a')
            ->where('`type` = ' . (int) $type);
        if ($id_customer) {
            $sql->where('`id_customer` = ' . (int) $id_customer);
        }
        if ($id_order && $type == self::TYPE_NOTE_ORDER) {
            $sql->where('`id_order` = ' . (int) $id_order);
        }

        return (int) Db::getInstance()->getValue($sql);
    }

    /**
     * Summary of getList
     * Retrieves all notes for a customer, an order and a search text
     * and format result into a tbody html
     *
     * @param int $type
     * @param int $id_customer
     * @param int $id_order
     * @param string $text
     *
     * @return string List of notes in HTML format, returns tbody
     */
    public static function getListNotesTbody($type, $id_customer, $id_order = 0, $text = '')
    {
        $context = Context::getContext();
        $module = Module::getInstanceByName('mpnotes');

        $sql = new DbQuery();
        $sql->select('*')
            ->from(self::$definition['table'])
            ->where('`type` = ' . (int) $type)
            ->where('`id_customer` = ' . (int) $id_customer)
            ->orderBy('`date_add` DESC');

        if ($id_order && $type == self::TYPE_NOTE_ORDER) {
            $sql->where('`id_order` = ' . (int) $id_order);
        }

        if ($text) {
            $sql->where('`note` LIKE \'%' . pSQL($text) . '%\'');
        }
        $rows = Db::getInstance()->executeS($sql);

        if ($rows) {
            foreach ($rows as &$row) {
                $row['link'] = $context->link->getAdminLink('AdminOrders', true, [], ['id_order' => $row['id_order'], 'vieworder' => 1]);
                $row['type'] = (int) $type;
                $row['attachments'] = ModelMpNoteAttachment::getAttachments($row['id_mp_note']);
            }
        }

        $path = $module->getLocalPath() . 'views/templates/admin/partials/tbody/tbodyNote.tpl';
        $tpl = $context->smarty->createTemplate($path);
        $tpl->assign('note_list', $rows);

        return $tpl->fetch();
    }

    public static function showNote($noteId, $rowId, $tableName, $noteTypeId, $uploadDir, $isNew = false)
    {
        $module = Module::getInstanceByName('mpnotes');
        $context = Context::getContext();
        $path = $module->getLocalPath() . 'views/templates/admin/notes/panelNote.tpl';

        switch ($noteTypeId) {
            case self::TYPE_NOTE_EMBROIDERY:
                $id_order = 0;
                $id_customer = $noteId;
                break;
            case self::TYPE_NOTE_ORDER:
                $id_order = $noteId;
                $id_customer = 0;
                break;
            case self::TYPE_NOTE_CUSTOMER:
                $id_order = 0;
                $id_customer = $noteId;
                break;
        }

        $tpl = $context->smarty->createTemplate($path);
        $tpl->assign([
            'link' => $context->link,
            'id_customer' => $id_customer,
            'id_order' => $id_order,
            'note' => self::getNote($noteTypeId, $id_customer, $id_order, $rowId, $isNew),
            'showSave' => $noteTypeId == self::TYPE_NOTE_EMBROIDERY,
            'uploadDir' => $uploadDir,
            'tableName' => $tableName,
            'isNew' => $isNew,
        ]);

        return $tpl->fetch();
    }
}