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
    public $id_mpnote;
    public $id_import;
    public $id_parent;
    public $id_customer;
    public $id_order;
    public $id_employee;
    public $type;
    public $reference;
    public $customer_firstname;
    public $customer_lastname;
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
            'id_import' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'default' => 0],
            'id_parent' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'default' => 0],
            'id_mpnote' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'default' => 0],
            'id_customer' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'default' => 0],
            'id_order' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'default' => 0],
            'id_employee' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'default' => 0],
            'type' => ['type' => self::TYPE_STRING, 'validate' => 'isAnything', 'required' => true, 'size' => 16],
            'reference' => ['type' => self::TYPE_STRING, 'validate' => 'isAnything', 'required' => true, 'size' => 64],
            'customer_firstname' => ['type' => self::TYPE_STRING, 'validate' => 'isAnything', 'required' => false, 'size' => 64],
            'customer_lastname' => ['type' => self::TYPE_STRING, 'validate' => 'isAnything', 'required' => false, 'size' => 64],
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

    public static function getAttachments($idNote = null, $typeNote = null)
    {
        $sql = new DbQuery();
        $sql
            ->select('*')
            ->from(self::$definition['table'])
            ->orderBy('id_mpnote_attachment ASC');

        if ($idNote) {
            $sql->where('id_mpnote = ' . (int) $idNote);
        }

        if ($typeNote) {
            $sql->where("type = '" . pSQL($typeNote) . "'");
        }

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

    public static function getAttachmentsCount($idNote = null, $typeNote = null)
    {
        $sql = new DbQuery();
        $sql
            ->select('COUNT(id_mpnote_attachment)')
            ->from(self::$definition['table'])
            ->orderBy('id_mpnote_attachment ASC');

        if ($idNote) {
            $sql->where('id_mpnote = ' . (int) $idNote);
        }

        if ($typeNote) {
            $sql->where("type = '" . pSQL($typeNote) . "'");
        }

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
                `id_import` int(10) UNSIGNED NOT NULL DEFAULT 0,
                `id_parent` int(10) UNSIGNED NOT NULL DEFAULT 0,
                `id_mpnote` int(10) UNSIGNED NOT NULL DEFAULT 0,
                `id_customer` int(10) UNSIGNED NOT NULL DEFAULT 0,
                `id_order` int(10) UNSIGNED NOT NULL DEFAULT 0,
                `id_employee` int(11) UNSIGNED NOT NULL DEFAULT 0,
                `type` varchar(16) NOT NULL COMMENT '#customer,#order,#embroidery',
                `reference` varchar(64) DEFAULT NULL,
                `customer_firstname` varchar(64) DEFAULT NULL,
                `customer_lastname` varchar(64) DEFAULT NULL,
                `employee_firstname` varchar(64) DEFAULT '',
                `employee_lastname` varchar(64) DEFAULT '',
                `filename` varchar(255) NOT NULL,
                `filetitle` varchar(255) NOT NULL,
                `file_ext` varchar(16) NOT NULL,
                `deleted` tinyint(1) UNSIGNED NOT NULL DEFAULT 0,
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
