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

use MpSoft\MpNotes\Helpers\GetTwigEnvironment;
use MpSoft\MpNotes\Helpers\ImportFromV16;
use MpSoft\MpNotes\Helpers\JsonDecoder;
use \Customer;
use \Db;
use \DbQuery;
use \Employee;
use \ObjectModel;
use \Order;
use \Tools;
use \Validate;

class ModelMpNote extends ObjectModel
{
    const TYPE_CUSTOMER = 'customer';
    const TYPE_ORDER = 'order';
    const TYPE_EMBROIDERY = 'embroidery';

    public $id_history;

    public $type;

    public $reference;

    public $id_customer;

    public $id_order;

    public $id_employee;

    public $employee_firstname;

    public $employee_lastname;

    public $gravity;

    public $content;

    public $printable;

    public $chat;

    public $deleted;

    public $date_add;

    public $date_upd;

    protected $customer_firstname;

    protected $customer_lastname;

    protected $gravityIcons = [
        'info' => 'info',
        'warning' => 'warning',
        'error' => 'error',
        'success' => 'check_circle',
    ];

    public static $definition = [
        'table' => 'mpnote',
        'primary' => 'id_mpnote',
        'fields' => [
            'id_history' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'default' => 0],
            'type' => ['type' => self::TYPE_STRING, 'validate' => 'isString', 'size' => 16, 'default' => 'undefined'],
            'reference' => ['type' => self::TYPE_STRING, 'validate' => 'isString', 'size' => 16, 'default' => 'undefined'],
            'id_customer' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId'],
            'id_order' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'default' => 0],
            'id_employee' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId'],
            'employee_firstname' => ['type' => self::TYPE_STRING, 'validate' => 'isGenericName', 'size' => 64],
            'employee_lastname' => ['type' => self::TYPE_STRING, 'validate' => 'isGenericName', 'size' => 64],
            'gravity' => ['type' => self::TYPE_STRING, 'validate' => 'isString', 'size' => 16, 'default' => 'INFO'],
            'content' => ['type' => self::TYPE_STRING, 'validate' => 'isAnything', 'required' => true, 'size' => 99999999],
            'printable' => ['type' => self::TYPE_BOOL, 'validate' => 'isBool', 'required' => false, 'default' => 0],
            'chat' => ['type' => self::TYPE_BOOL, 'validate' => 'isBool', 'required' => false, 'default' => 0],
            'deleted' => ['type' => self::TYPE_BOOL, 'validate' => 'isBool', 'required' => false, 'default' => 0],
            'date_add' => ['type' => self::TYPE_DATE, 'validate' => 'isDateFormat'],
            'date_upd' => ['type' => self::TYPE_DATE, 'validate' => 'isDateFormat', 'required' => false],
        ],
    ];

    public function getFieldsList()
    {
        if ($this->id_customer) {
            $customer = new Customer($this->id_customer);
            if (Validate::isLoadedObject($customer)) {
                $this->customer_firstname = Tools::strtoupper($customer->firstname);
                $this->customer_lastname = Tools::strtoupper($customer->lastname);
            } else {
                $this->customer_firstname = '';
                $this->customer_lastname = '';
            }
        }
        return [
            'id' => $this->id,
            'id_history' => $this->id_history,
            'type' => $this->type,
            'reference' => $this->reference,
            'id_customer' => $this->id_customer,
            'customer_firstname' => $this->customer_firstname,
            'customer_lastname' => $this->customer_lastname,
            'id_order' => $this->id_order,
            'id_employee' => $this->id_employee,
            'employee_firstname' => $this->employee_firstname,
            'employee_lastname' => $this->employee_lastname,
            'gravity' => $this->gravity,
            'content' => $this->content,
            'printable' => (int) $this->printable,
            'chat' => (int) $this->chat,
            'deleted' => $this->deleted,
            'date_add' => $this->date_add,
            'date_upd' => $this->date_upd,
        ];
    }

    public static function getStaticFieldsList($id)
    {
        $model = new self($id);
        return $model->getFieldsList();
    }

    public static function getNotesCountByTypeAndIdOrder($id_order, $type)
    {
        $db = Db::getInstance();
        $query = new DbQuery();
        $order = new Order($id_order);
        if (!Validate::isLoadedObject($order)) {
            return 0;
        }

        $customer = new Customer($order->id_customer);
        if (!Validate::isLoadedObject($customer)) {
            return 0;
        }

        $type = pSQL($type);
        $query
            ->select('COUNT(*)')
            ->from('mpnote')
            ->where("type = '{$type}'");

        switch ($type) {
            case self::TYPE_CUSTOMER:
                $query->where('id_customer = ' . (int) $customer->id);
                break;
            case self::TYPE_ORDER:
                $query->where('id_order = ' . (int) $order->id);
                break;
            case self::TYPE_EMBROIDERY:
                $query->where('id_customer = ' . (int) $customer->id);
                break;
            default:
                return false;
        }

        return $db->getValue($query);
    }

    public static function getNotesByTypeAndIdOrder($id_order, $type, $search = '')
    {
        $db = Db::getInstance();
        $query = new DbQuery();
        $order = new Order($id_order);
        if (!Validate::isLoadedObject($order)) {
            return [];
        }

        $customer = new Customer($order->id_customer);
        if (!Validate::isLoadedObject($customer)) {
            return [];
        }

        $query
            ->select('*, id_mpnote as id')
            ->from('mpnote')
            ->where("type = '" . $type . "'")
            ->orderBy('date_add DESC');

        switch ($type) {
            case self::TYPE_CUSTOMER:
                $query->where('id_customer = ' . (int) $customer->id);
                break;
            case self::TYPE_ORDER:
                $query->where('id_order = ' . (int) $order->id);
                break;
            case self::TYPE_EMBROIDERY:
                $query->where('id_customer = ' . (int) $customer->id);
                break;
            case 'all':
                break;
        }

        if ($search) {
            $search = pSQL($search);
            $query->where('content LIKE "%' . pSQL($search) . '%"');
        }

        $notes = $db->executeS($query);

        $employees = [];
        foreach ($notes as &$note) {
            if (!isset($employees[$note['id_employee']])) {
                $employee = new Employee($note['id_employee']);
                $employees[$note['id_employee']] = $employee;
            }
            $note['employee'] = $employees[$note['id_employee']]->firstname . ' ' . $employees[$note['id_employee']]->lastname;
            $note['gravity_icon'] = self::$gravityIcons[$note['gravity']] ?? 'help';
            $note['attachments'] = self::getAttachments($note['id'], $type);
            $note['editOrderUrl'] = \Context::getContext()->link->getAdminLink('AdminOrders', true, [], ['id_order' => (int) $note['id_order'], 'vieworder' => 1]);
        }

        return $notes;
    }

    public static function getAllNotes($search = '', $limit, $offset, $sort = 'id_mpnote', $order = 'DESC', $type = 'all')
    {
        $db = Db::getInstance();
        $queryCount = new DbQuery();
        $queryCount
            ->select('COUNT(*)')
            ->from('mpnote', 'a');
        $totalRows = (int) $db->getValue($queryCount);

        $query = new DbQuery();
        $query
            ->select('a.*, GROUP_CONCAT(DISTINCT b.id_mpnote_attachment) as attachments')
            ->from('mpnote', 'a')
            ->leftJoin(ModelMpNoteAttachment::$definition['table'], 'b', 'a.id_mpnote = b.id_mpnote')
            ->groupBy('a.id_mpnote')
            ->orderBy($sort . ' ' . $order);

        if ($type != 'all') {
            $query->where("a.type = '{$type}'");
            $queryCount->where("a.type = '{$type}'");
        }

        if ($search) {
            $search = pSQL($search);
            $query->where("a.content LIKE '%{$search}%' OR a.employee_firstname LIKE '%{$search}%' OR a.employee_lastname LIKE '%{$search}%'");
            $queryCount->where("a.content LIKE '%{$search}%' OR employee_firstname LIKE '%{$search}%' OR employee_lastname LIKE '%{$search}%'");
        }

        if ($limit) {
            $query->limit((int) $limit, (int) $offset);
        }

        $notes = $db->executeS($query);
        $filtered = $db->getValue($queryCount);

        return [
            'rows' => $notes,
            'total' => $filtered,
            'offset' => $offset,
            'limit' => $limit,
            'totalNotFiltered' => $totalRows,
            'query' => $query->build(),
        ];
    }

    public static function getNoteDetails($idNote)
    {
        $note = new self($idNote);
        if (!Validate::isLoadedObject($note)) {
            return [];
        }
        return $note->getFieldsList();
    }

    public static function getNotesByType($params)
    {
        /*
         * $params = [
         *     'search' => Tools::getValue('search'),
         *     'id_order' => Tools::getValue('orderId'),
         *     'orderBy' => Tools::getValue('sort'),
         *     'sort' => Tools::getValue('order'),
         *     'limit' => Tools::getValue('limit'),
         *     'offset' => Tools::getValue('offset'),
         *     'type' => Tools::getValue('type'),
         * ];
         */

        $db = Db::getInstance();

        $queryCustomer = new DbQuery();
        $queryCustomer
            ->select('c.id_customer, c.firstname, c.lastname')
            ->from('orders', 'o')
            ->innerJoin('customer', 'c', 'o.id_customer=c.id_customer')
            ->where('o.id_order=' . (int) $params['id_order']);
        $customer = $db->getRow($queryCustomer);
        if ($customer) {
            $customer['name'] = Tools::ucwords($customer['firstname'] . ' ' . $customer['lastname']);
        } else {
            return [];
        }

        $queryCount = new DbQuery();
        $queryCount
            ->select('COUNT(*)')
            ->from('mpnote', 'a');
        $totalRows = (int) $db->getValue($queryCount);

        $query = new DbQuery();
        $query
            ->select('a.*, GROUP_CONCAT(DISTINCT b.id_mpnote_attachment) as attachments')
            ->from('mpnote', 'a')
            ->leftJoin(ModelMpNoteAttachment::$definition['table'], 'b', 'a.id_mpnote = b.id_mpnote')
            ->where("a.type = '{$params['type']}'")
            ->groupBy('a.id_mpnote')
            ->orderBy($params['orderBy'] . ' ' . $params['sort']);

        if ($params['type'] == 'customer') {
            $query->where("a.id_customer = {$customer['id_customer']}");
            $queryCount->where("a.id_customer = {$customer['id_customer']}");
        }

        if ($params['type'] == 'order') {
            $query->where("a.id_order = {$params['id_order']}");
            $queryCount->where("a.id_order = {$params['id_order']}");
        }

        if ($params['type'] == 'embroidery') {
            $query->where("a.id_customer = {$customer['id_customer']}");
            $queryCount->where("a.id_customer = {$customer['id_customer']}");
        }

        if ($params['search']) {
            $search = pSQL($params['search']);
            $query->where("a.content LIKE '%{$search}%' OR a.employee_firstname LIKE '%{$search}%' OR a.employee_lastname LIKE '%{$search}%'");
            $queryCount->where("a.content LIKE '%{$search}%' OR a.employee_firstname LIKE '%{$search}%' OR a.employee_lastname LIKE '%{$search}%'");
        }

        if ($params['limit']) {
            $query->limit((int) $params['limit'], (int) $params['offset']);
        }

        $notes = $db->executeS($query);
        $filtered = $db->getValue($queryCount);

        foreach ($notes as &$note) {
            $note['attachments'] = self::renderAttachments($note['attachments']);
            $note['editOrderUrl'] = \Context::getContext()->link->getAdminLink('AdminOrders', true, [], ['id_order' => (int) $note['id_order'], 'vieworder' => 1]);
        }

        return [
            'rows' => $notes,
            'total' => $filtered,
            'offset' => $params['offset'],
            'limit' => $params['limit'],
            'totalNotFiltered' => $totalRows,
            'query' => $query->build(),
        ];
    }

    public static function renderAttachments($attachments)
    {
        $module = \Module::getInstanceByName('mpnotes');
        $twig = new GetTwigEnvironment($module->name);

        if (!$attachments) {
            $attachments = [];
        } else {
            $attachments = explode(',', $attachments);
        }

        $images = [];
        foreach ($attachments as $item) {
            $attachment = new ModelMpNoteAttachment((int) $item);
            if (Validate::isLoadedObject($attachment)) {
                $filename = $attachment->filename;
                $filetitle = $attachment->filetitle;
                $path = _PS_IMG_DIR_ . 'mpnotes/' . trim(ltrim($filename, '/'));
                if (file_exists($path)) {
                    if ($attachment->file_ext == 'pdf') {
                        // Prova a convertire PDF in immagine base64
                        $pdfPreview = self::pdfToBase64Image($path);
                        if ($pdfPreview) {
                            $src = $pdfPreview;
                        } else {
                            // Fallback: usa icona PDF
                            $src = self::getPdfIconBase64();
                        }
                    } else {
                        $src = \Context::getContext()->link->getBaseLink()
                            . ltrim(_PS_IMG_, '/') . 'mpnotes/' . trim(ltrim($filename, '/'));
                    }
                } else {
                    $src = \Context::getContext()->link->getBaseLink() . _PS_IMG_ . '404.gif';
                }

                $images[] = [
                    'filename' => $src,
                    'filetitle' => $filetitle,
                    'path' => $path,
                    'id' => (int) $item,
                ];
            }
        }

        $template = $twig->load('@ModuleTwig/admin/attachments-preview.html.twig');
        $html = $template->render(['images' => $images]);

        return $html;
    }

    protected static function pdfToBase64Image($pdfPath)
    {
        if (!file_exists($pdfPath)) {
            return null;
        }

        // Verifica se Imagick è disponibile
        if (!extension_loaded('imagick')) {
            return null;
        }

        try {
            $imagick = new \Imagick();
            $imagick->setResolution(150, 150);
            $imagick->readImage($pdfPath . '[0]');  // Prima pagina
            $imagick->setImageFormat('png');
            $imagick->thumbnailImage(200, 200, true);

            $imageBlob = $imagick->getImageBlob();
            $base64 = base64_encode($imageBlob);

            $imagick->clear();
            $imagick->destroy();

            return 'data:image/png;base64,' . $base64;
        } catch (\Exception $e) {
            return null;
        }
    }

    protected static function getPdfIconBase64()
    {
        // Icona PDF generica in SVG base64
        $svg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="100" height="100">
            <rect width="24" height="24" fill="#f5f5f5"/>
            <path fill="#d32f2f" d="M14,2H6A2,2 0 0,0 4,4V20A2,2 0 0,0 6,22H18A2,2 0 0,0 20,20V8L14,2M18.5,9L13,3.5L13,9H18.5M6,20V4H11V10H18V20H6Z"/>
            <text x="12" y="16" font-family="Arial" font-size="4" fill="#d32f2f" text-anchor="middle" font-weight="bold">PDF</text>
        </svg>';

        return 'data:image/svg+xml;base64,' . base64_encode($svg);
    }

    public static function getAttachmentsCount($id_note)
    {
        $query = new DbQuery();
        $query
            ->select('count(' . ModelMpNoteAttachment::$definition['primary'] . ')')
            ->from(ModelMpNoteAttachment::$definition['table'])
            ->where('id_mpnote = ' . (int) $id_note);

        return (int) Db::getInstance()->getValue($query);
    }

    public static function getAttachments($id_note, $type)
    {
        $query = new DbQuery();
        $query
            ->select('*')
            ->from(ModelMpNoteAttachment::$definition['table'])
            ->where('type = ' . (int) $type)
            ->where('id_mpnote = ' . (int) $id_note);

        return Db::getInstance()->executeS($query);
    }

    public static function install()
    {
        $pfx = _DB_PREFIX_;
        $engine = _MYSQL_ENGINE_;
        $QUERY = "
            CREATE TABLE IF NOT EXISTS `{$pfx}mpnote` (
                `id_mpnote` int(10) NOT NULL AUTO_INCREMENT,
                `type` varchar(16) DEFAULT NULL,
                `id_order` int(10) DEFAULT 0,
                `id_customer` int(10) DEFAULT NULL,
                `id_employee` int(10) DEFAULT NULL,
                `employee_firstname` varchar(64) DEFAULT NULL,
                `employee_lastname` varchar(64) DEFAULT NULL,
                `gravity` varchar(16) DEFAULT 'info',
                `content` text NOT NULL,
                `printable` tinyint(1) DEFAULT 0,
                `chat` tinyint(1) DEFAULT 0,
                `deleted` tinyint(1) DEFAULT 0,
                `date_add` datetime DEFAULT NULL,
                `date_upd` datetime DEFAULT NULL,
                PRIMARY KEY (`id_mpnote`),
                KEY `id_note_type` (`id_note_type`),
                KEY `id_customer` (`id_customer`),
                KEY `id_employee` (`id_employee`),
                KEY `id_order` (`id_order`)
            ) ENGINE={$engine}
        ";

        return Db::getInstance()->execute($QUERY);
    }

    public static function import($offset, $limit)
    {
        $module = \Module::getInstanceByName('mpnotes');
        $import = new ImportFromV16($module);
        $data = $import->getData($offset, $limit);
        $result = $import->doImport($data);

        return $result;
    }

    public static function updateFlags()
    {
        $pfx = _DB_PREFIX_;
        $tablename = $pfx . self::$definition['table'];

        $QUERY = "
            UPDATE {$tablename}
            SET 
                printable = CASE 
                    WHEN JSON_VALID(flags) = 1 
                        AND JSON_EXTRACT(flags, '\$.printable') IS NOT NULL
                    THEN CAST(JSON_UNQUOTE(JSON_EXTRACT(flags, '\$.printable')) AS UNSIGNED) = 1
                    ELSE printable 
                END,
                chat = CASE 
                    WHEN JSON_VALID(flags) = 1 
                        AND JSON_EXTRACT(flags, '\$.chat') IS NOT NULL
                    THEN CAST(JSON_UNQUOTE(JSON_EXTRACT(flags, '\$.chat')) AS UNSIGNED) = 1
                    ELSE chat 
                END
            WHERE 
                flags IS NOT NULL 
                AND flags != '';
        ";

        return Db::getInstance()->execute($QUERY);
    }
}
