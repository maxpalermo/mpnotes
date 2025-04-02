<?php

use MpSoft\MpNotes\Helpers\JsonDecoder;

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
    public $id_note_type; // Tipo di messaggio (vedi tabella mp_note_type)
    public $id_customer;
    public $id_employee;
    public $id_order;
    public $gravity;
    public $content;
    public $flags;
    public $deleted;
    public $date_add;
    public $date_upd;
    protected $allow_update;
    protected $allow_attachments;

    public static $definition = [
        'table' => 'mp_note',
        'primary' => 'id_mp_note',
        'fields' => [
            'id_note_type' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedInt'],
            'id_customer' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId'],
            'id_employee' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId'],
            'id_order' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'default' => 0],
            'gravity' => ['type' => self::TYPE_STRING, 'validate' => 'isString', 'size' => 16, 'default' => 'INFO'],
            'content' => ['type' => self::TYPE_STRING, 'validate' => 'isAnything', 'required' => true, 'size' => 999999],
            'flags' => ['type' => self::TYPE_STRING, 'validate' => 'isAnything', 'required' => false],
            'deleted' => ['type' => self::TYPE_BOOL, 'validate' => 'isBool', 'required' => false, 'default' => 0],
            'date_add' => ['type' => self::TYPE_DATE, 'validate' => 'isDateFormat'],
            'date_upd' => ['type' => self::TYPE_DATE, 'validate' => 'isDateFormat', 'required' => false],
        ],
    ];

    public function __construct($id = null, $id_lang = null, $id_shop = null, $translator = null)
    {
        parent::__construct($id, $id_lang, $id_shop, $translator);
        if ($this->flags && JsonDecoder::isJson($this->flags)) {
            $this->flags = JsonDecoder::decodeJson($this->flags, []);
        }

        if ($this->id_note_type) {
            $this->setNoteType($this->id_note_type);
            foreach ($this->flags as &$flag) {
                $om = new ModelMpNoteFlag($flag['id']);
                if (!\Validate::isLoadedObject($om)) {
                    $flag['name'] = '--';
                    $flag['color'] = '#c3c3c3';
                    $flag['icon'] = 'help';
                    $flag['allow_update'] = false;
                    $flag['allow_attachments'] = false;
                } else {
                    $flag['name'] = $om->name;
                    $flag['color'] = $om->color;
                    $flag['icon'] = $om->icon;
                    $flag['allow_update'] = $om->allow_update;
                    $flag['allow_attachments'] = $om->allow_attachments;
                }
            }
        } else {
            $flags = ModelMpNoteFlag::getDefaultFlags();
            $this->flags = $flags;
        }
    }

    public function setNoteType($id_type)
    {
        $this->id_note_type = $id_type;
        $type = new ModelMpNoteFlag($this->id_note_type);
        if (!\Validate::isLoadedObject($type)) {
            $this->allow_update = 0;
            $this->allow_attachments = 0;
        } else {
            $this->allow_update = $type->allow_update;
            $this->allow_attachments = $type->allow_attachments;
        }
    }

    public function setOrderId($id_order)
    {
        $this->id_order = $id_order;
    }

    public function setCustomerId($id_customer)
    {
        $this->id_customer = $id_customer;
    }

    public function setEmployeeId($id_employee)
    {
        $this->id_employee = $id_employee;
    }

    public function setGravity($gravity)
    {
        $this->gravity = $gravity;
    }

    public function setFlags($flags = [])
    {
        if (!is_array($flags)) {
            $this->flags = [];

            return;
        }

        if (!$flags) {
            $defaultFlags = ModelMpNoteFlag::getDefaultFlags();
            $this->flags = $defaultFlags;

            return;
        }

        $this->flags = $flags;
    }

    public function setDeleted($deleted)
    {
        $this->deleted = $deleted;
    }

    public function setDateAdd($date_add)
    {
        $this->date_add = $date_add;
    }

    public function setDateUpd($date_upd)
    {
        $this->date_upd = $date_upd;
    }

    public function getContent()
    {
        return $this->content;
    }

    public function getFlags()
    {
        return $this->flags;
    }

    public function getDeleted()
    {
        return $this->deleted;
    }

    public function getDateAdd()
    {
        return $this->date_add;
    }

    public function getDateUpd()
    {
        return $this->date_upd;
    }

    public static function getNoteTypes()
    {
        $db = Db::getInstance();
        $query = new DbQuery();
        $query->select('id_mp_note_flag, name')
            ->from('mp_note_flag')
            ->orderBy('name ASC');
        $result = $db->executeS($query);

        return $result;
    }

    public function add($autodate = true, $null_values = false)
    {
        if ($this->flags && is_array($this->flags)) {
            $this->flags = json_encode($this->flags);
        }

        return parent::add($autodate, $null_values);
    }

    public function update($null_values = false)
    {
        if ($this->flags && is_array($this->flags)) {
            $this->flags = json_encode($this->flags);
        }

        return parent::update($null_values);
    }

    public function allowUpdate()
    {
        return $this->allow_update;
    }

    public function allowAttachments()
    {
        return $this->allow_attachments;
    }

    public function getFieldsList()
    {
        return [
            'id' => $this->id,
            'id_mp_note' => $this->id,
            'id_order' => $this->id_order,
            'id_customer' => $this->id_customer,
            'id_employee' => $this->id_employee,
            'id_note_type' => $this->id_note_type,
            'gravity' => $this->gravity,
            'content' => $this->content,
            'flags' => $this->flags,
            'allow_update' => $this->allow_update,
            'allow_attachments' => $this->allow_attachments,
            'deleted' => $this->deleted,
            'date_add' => $this->date_add,
            'date_upd' => $this->date_upd,
        ];
    }
}
