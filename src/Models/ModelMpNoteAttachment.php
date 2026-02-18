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

namespace MpSoft\MpNotes\Models;

use MpSoft\MpNotes\Helpers\JsonDecoder;
use \Customer;
use \Db;
use \DbQuery;
use \Employee;
use \ObjectModel;
use \Order;
use \Tools;

class ModelMpNoteAttachment extends ObjectModel
{
    public $id_history;
    public $type;
    public $id_mpnote;
    public $reference;
    public $id_customer;
    public $id_order;
    public $id_employee;
    public $employee_firstname;
    public $employee_lastname;
    public $filename;
    public $filetitle;
    public $file_ext;
    public $deleted;
    public $date_add;
    public $date_upd;

    public static $definition = [
        'table' => 'mpnote_attachment',
        'primary' => 'id_mpnote_attachment',
        'fields' => [
            'id_history' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId'],
            'type' => ['type' => self::TYPE_STRING, 'validate' => 'isAnything', 'required' => true, 'size' => 16],
            'id_mpnote' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId'],
            'reference' => ['type' => self::TYPE_STRING, 'validate' => 'isAnything', 'required' => true, 'size' => 64],
            'id_customer' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId'],
            'id_order' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId'],
            'id_employee' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId'],
            'employee_firstname' => ['type' => self::TYPE_STRING, 'validate' => 'isAnything', 'required' => false, 'size' => 64],
            'employee_lastname' => ['type' => self::TYPE_STRING, 'validate' => 'isAnything', 'required' => false, 'size' => 64],
            'filename' => ['type' => self::TYPE_STRING, 'validate' => 'isAnything', 'required' => true, 'size' => 255],
            'filetitle' => ['type' => self::TYPE_STRING, 'validate' => 'isAnything', 'required' => true, 'size' => 255],
            'file_ext' => ['type' => self::TYPE_STRING, 'validate' => 'isAnything', 'required' => true, 'size' => 16],
            'deleted' => ['type' => self::TYPE_BOOL, 'validate' => 'isBool', 'required' => false, 'default' => 0],
            'date_add' => ['type' => self::TYPE_DATE, 'validate' => 'isDateFormat'],
            'date_upd' => ['type' => self::TYPE_DATE, 'validate' => 'isDateFormat', 'required' => false],
        ],
    ];

    public static function getAttachments($idNote, $typeNote)
    {
        $sql = new DbQuery();
        $sql
            ->select('*')
            ->from(self::$definition['table'])
            ->where('id_mpnote = ' . (int) $idNote)
            ->where('type_note = ' . (int) $typeNote)
            ->orderBy('id_mpnote_attachment ASC');

        $result = Db::getInstance()->executeS($sql);

        if ($result) {
            foreach ($result as &$value) {
                $value['url'] =
                    \Context::getContext()->link->getBaseLink()
                    . 'img/mpnotes/'
                    . $value['filename'];
            }
        }

        return $result;
    }

    public static function getAttachmentsCount($idNote, $typeNote)
    {
        $sql = new DbQuery();
        $sql
            ->select('COUNT(id_mpnote_attachment)')
            ->from(self::$definition['table'])
            ->where('id_mpnote = ' . (int) $idNote)
            ->where('type_note = ' . (int) $typeNote)
            ->orderBy('id_mpnote_attachment ASC');

        $result = (int) Db::getInstance()->getValue($sql);

        return $result;
    }

    public static function install()
    {
        $pfx = _DB_PREFIX_;
        $engine = _MYSQL_ENGINE_;
        $QUERY = "
            CREATE TABLE IF NOT EXISTS `{$pfx}mpnote_attachment` (
                `id_mpnote_attachment` int(10) NOT NULL AUTO_INCREMENT,
                `id_history` int(11) DEFAULT 0,
                `type` varchar(16) NOT NULL,
                `id_mpnote` int(10) DEFAULT NULL,
                `reference` varchar(64) DEFAULT NULL,
                `id_customer` int(10) DEFAULT NULL,
                `id_order` int(10) DEFAULT NULL,
                `id_employee` int(11) DEFAULT 0,
                `employee_firstname` varchar(64) DEFAULT '',
                `employee_lastname` varchar(64) DEFAULT '',
                `filename` varchar(255) NOT NULL,
                `filetitle` varchar(255) NOT NULL,
                `file_ext` varchar(16) NOT NULL,
                `deleted` tinyint(1) DEFAULT 0,
                `date_add` datetime DEFAULT NULL,
                `date_upd` datetime DEFAULT NULL,
                PRIMARY KEY (`id_mpnote_attachment`),
                KEY `id_mp_note` (`id_mpnote`),
                KEY `id_customer` (`id_customer`),
                KEY `id_order` (`id_order`)
            ) ENGINE={$engine}
        ";

        return Db::getInstance()->execute($QUERY);
    }
}
