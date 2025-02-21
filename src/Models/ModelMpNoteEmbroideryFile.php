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
class ModelMpNoteEmbroideryFile extends ObjectModel
{
    public $id_mp_note_embroidery;
    public $id_employee;
    public $filename;
    public $filetitle;
    public $file_ext;
    public $date_add;
    public $date_upd;

    public static $definition = [
        'table' => 'mp_note_embroidery_file',
        'primary' => 'id_mp_note_embroidery_file',
        'fields' => [
            'id_mp_note_embroidery' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId'],
            'id_employee' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId'],
            'filename' => ['type' => self::TYPE_STRING, 'validate' => 'isCleanHtml', 'required' => true],
            'filetitle' => ['type' => self::TYPE_STRING, 'validate' => 'isCleanHtml', 'required' => true],
            'file_ext' => ['type' => self::TYPE_STRING, 'validate' => 'isCleanHtml', 'required' => true, 'size' => 16],
            'date_add' => ['type' => self::TYPE_DATE, 'validate' => 'isDateFormat'],
            'date_upd' => ['type' => self::TYPE_DATE, 'validate' => 'isDateFormat', 'required' => false],
        ],
    ];

    public static function getAttachments($id_mp_note_embroidery)
    {
        $sql = new DbQuery();
        $sql->select('*')
            ->from(self::$definition['table'], 'a')
            ->where('a.id_mp_note_embroidery = ' . (int) $id_mp_note_embroidery)
            ->orderBy('a.id_mp_note_embroidery_file ASC');

        $result = Db::getInstance()->executeS($sql);

        return $result;
    }
}
