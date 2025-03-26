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
    public $content;
    public $flags;
    public $deleted;
    public $date_add;
    public $date_upd;

    public static $definition = [
        'table' => 'mp_note',
        'primary' => 'id_mp_note',
        'fields' => [
            'id_note_type' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedInt'],
            'id_customer' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId'],
            'id_employee' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId'],
            'id_order' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'default' => 0],
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
}
