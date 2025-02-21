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
if (!defined('_PS_VERSION_')) {
    exit;
}

require_once dirname(__FILE__) . '/vendor/autoload.php';
require_once dirname(__FILE__) . '/src/Models/autoload.php';

class MpNotes extends Module
{
    protected $fetchHandler;

    public function __construct()
    {
        $this->name = 'mpnotes';
        $this->tab = 'administration';
        $this->version = '1.0.0';
        $this->author = 'Massimiliano Palermo';
        $this->need_instance = 0;
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->l('MP Note');
        $this->description = $this->l('Gestisci note dei clienti, note degli ordini e note dei ricami');
        $this->ps_versions_compliancy = ['min' => '8.2.0', 'max' => _PS_VERSION_];

        $this->fetchHandler = new MpSoft\MpNotes\Helpers\FetchHandler('MpSoft\MpNotes\Fetch\ModuleFetch');
    }

    public function install()
    {
        return parent::install()
            && $this->registerHook('actionAdminControllerSetMedia')
            && $this->registerHook('displayAdminEndContent')
            && $this->registerHook('displayAdminOrder')
            && $this->registerHook('displayAdminOrderTop')
            && $this->installTabs();
    }

    public function uninstall()
    {
        return parent::uninstall()
            && $this->uninstallTabs();
    }

    public function installTabs()
    {
        $tabs = [
            [
                'class_name' => 'AdminCustomerNotes',
                'visible' => true,
                'name' => 'Note Cliente',
                'parent_class_name' => 'AdminParentCustomer',
                'icon' => 'user',
            ],
            [
                'class_name' => 'AdminOrderNotes',
                'visible' => true,
                'name' => 'Note Ordini',
                'parent_class_name' => 'AdminParentCustomer',
                'icon' => 'note',
            ],
            [
                'class_name' => 'AdminEmbroideryNotes',
                'visible' => true,
                'name' => 'Note Ricami',
                'parent_class_name' => 'AdminParentCustomer',
                'icon' => 'cut',
            ],
            [
                'class_name' => 'AdminAjax',
                'visible' => true,
                'name' => 'Note MP',
                'parent_class_name' => -1,
            ],
        ];

        foreach ($tabs as $tab) {
            $tabId = \MpSoft\MpNotes\Helpers\InstallTab::findTabId($tab['class_name']);
            if (!$tabId) {
                $tabId = null;
            }

            $newTab = new Tab($tabId);
            $newTab->active = $tab['visible'];
            $newTab->class_name = $tab['class_name'];
            $newTab->name = [];
            foreach (Language::getLanguages() as $lang) {
                $newTab->name[$lang['id_lang']] = $tab['name'];
            }
            $newTab->id_parent = (int) $tabId;
            $newTab->module = $this->name;

            if (!$newTab->save()) {
                return false;
            }
        }

        return true;
    }

    public function uninstallTabs()
    {
        $tabs = ['AdminCustomerNotes', 'AdminOrderNotes', 'AdminEmbroideryNotes', 'AdminAjax'];

        foreach ($tabs as $class_name) {
            $id_tab = (int) \MpSoft\MpNotes\Helpers\InstallTab::findTabId($class_name);
            if ($id_tab) {
                $tab = new Tab($id_tab);
                $tab->delete();
            }
        }

        return true;
    }

    public function hookActionAdminControllerSetMedia()
    {
        $controller = $this->context->controller->controller_name;

        if ($controller === 'AdminCustomerNotes'
            || $controller === 'AdminOrderNotes'
            || $controller === 'AdminEmbroideryNotes'
            || ($controller === 'AdminModules' && Tools::getValue('configure') == $this->name)
            || $controller === 'AdminOrders') {
            // Add SweetAlert2
            $this->context->controller->addJS('https://cdn.jsdelivr.net/npm/sweetalert2@11');

            // Add module's JS and CSS
            $this->context->controller->addCSS($this->_path . 'views/css/mpnotes.css', 'all', 1001);

            // Add JS variables for AJAX calls
            Media::addJsDef([
                'baseAdminDir' => __PS_BASE_URI__ . basename(_PS_ADMIN_DIR_),
                'token' => Tools::getAdminTokenLite('AdminMpNotes'),
            ]);
        }

        if (Tools::strtolower($controller) === 'adminorders') {
            $this->context->controller->addJS($this->_path . 'views/js/order/order-handle.js', 1001);
        }
    }

    public function hookDisplayAdminEndContent()
    {
        // Add your code for displaying content at the end of admin pages
        return '';
    }

    public function hookDisplayAdminOrder($params)
    {
        return '';
    }

    public function hookDisplayAdminOrderTop($params)
    {
        $id_order = $params['id_order'];
        $order = new Order($id_order);
        if (!Validate::isLoadedObject($order)) {
            return '';
        }

        $tpl = $this->context->smarty->createTemplate($this->getLocalPath() . 'views/templates/hook/summary.tpl');
        $tpl->assign([
            'adminURL' => $this->context->link->getAdminLink('AdminAjax'),
            'noteOrderUploadDir' => $this->getNoteUploadDir('order'),
            'noteEmbroideryUploadDir' => $this->getNoteUploadDir('embroidery'),
            'id_customer' => $order->id_customer,
            'id_order' => $order->id,
            'notes' => [
                'customer' => [
                    'id' => $order->id_customer,
                    'type' => 'customer',
                    'icon' => 'person',
                    'title' => 'Note cliente',
                    'table' => 'mp_note_customer',
                    'note_list' => $this->getNotes('customer', $order->id_customer),
                ],
                'order' => [
                    'id' => $order->id,
                    'type' => 'order',
                    'icon' => 'shopping_cart',
                    'title' => 'Note ordine',
                    'table' => 'mp_note_order',
                    'note_list' => $this->getNotes('order', $order->id),
                ],
                'embroidery' => [
                    'id' => $order->id_customer,
                    'type' => 'embroidery',
                    'icon' => 'content_cut',
                    'title' => 'Note ricami',
                    'table' => 'mp_note_embroidery',
                    'note_list' => $this->getNotes('embroidery', $order->id_customer),
                ],
            ],
        ]);

        return $tpl->fetch();
    }

    protected function getNoteUploadDir($type)
    {
        $path = _PS_UPLOAD_DIR_ . "mpnotes/{$type}";
        if (!is_dir($path)) {
            mkdir($path, 0775, true);
            copy(_PS_ROOT_DIR_ . '/index.php', $path . '/index.php');
        }

        $uploadFolder = basename(_PS_UPLOAD_DIR_);

        return $this->context->shop->getBaseURI() . "{$uploadFolder}/mpnotes/{$type}/";
    }

    public function getContent()
    {
        $tpl = $this->context->smarty->createTemplate($this->getLocalPath() . 'views/templates/content.tpl');

        $sql = 'SHOW TABLES LIKE "' . _DB_PREFIX_ . 'mp_note_customer"';
        $tableCustomerExists = Db::getInstance()->executeS($sql);

        $sql = 'SHOW TABLES LIKE "' . _DB_PREFIX_ . 'mp_note_order"';
        $tableOrderExists = Db::getInstance()->executeS($sql);

        $sql = 'SHOW TABLES LIKE "' . _DB_PREFIX_ . 'mp_note_embroidery"';
        $tableEmbroideryExists = Db::getInstance()->executeS($sql);

        $tpl->assign([
            'adminURL' => $this->context->link->getAdminLink('AdminModules') . '&configure=' . $this->name,
            'moduleDir' => $this->getPathUri(),
            'importPanels' => [
                [
                    'title' => 'Note clienti',
                    'description' => 'Importa note clienti',
                    'icon' => 'user',
                    'tablename' => 'mp_note_customer',
                    'action' => 'customer',
                    'exists' => $tableCustomerExists,
                    'rows' => $this->getRows('customer'),
                ],
                [
                    'title' => 'Note ordini',
                    'description' => 'Importa note ordini',
                    'icon' => 'document',
                    'tablename' => 'mp_note_order',
                    'action' => 'order',
                    'exists' => $tableOrderExists,
                    'rows' => $this->getRows('order'),
                ],
                [
                    'title' => 'Note ricami',
                    'description' => 'Importa note ricami',
                    'icon' => 'cut',
                    'tablename' => 'mp_note_embroidery',
                    'action' => 'embroidery',
                    'exists' => $tableEmbroideryExists,
                    'rows' => $this->getRows('embroidery'),
                ],
            ],
        ]);

        return $tpl->fetch();
    }

    protected function getRows($type)
    {
        switch ($type) {
            case 'customer':
                $sql = 'SELECT COUNT(*) as count FROM `' . _DB_PREFIX_ . 'mp_note_customer`';

                break;
            case 'order':
                $sql = 'SELECT COUNT(*) as count FROM `' . _DB_PREFIX_ . 'mp_note_order`';

                break;
            case 'embroidery':
                $sql = 'SELECT COUNT(*) as count FROM `' . _DB_PREFIX_ . 'mp_note_embroidery`';

                break;
        }

        return Db::getInstance()->getValue($sql);
    }

    protected function getNotes($type, $ref)
    {
        switch ($type) {
            case 'customer':
                $sql = 'SELECT * FROM `'
                    . _DB_PREFIX_ . 'mp_note_customer` '
                    . 'WHERE id_customer=' . (int) $ref
                    . ' ORDER BY date_add DESC';

                break;
            case 'order':
                $sql = new DbQuery();
                $sql->select('a.*')
                    ->from('mp_note_order', 'a')
                    ->where('a.id_order = ' . (int) $ref)
                    ->groupBy('a.id_mp_note_order')
                    ->orderBy('a.date_add DESC');
                $sql = $sql->build();

                break;
            case 'embroidery':
                $sql = new DbQuery();
                $sql->select('a.*')
                    ->from('mp_note_embroidery', 'a')
                    ->where('a.id_customer = ' . (int) $ref)
                    ->groupBy('a.id_mp_note_embroidery')
                    ->orderBy('a.date_add DESC');
                $sql = $sql->build();

                break;
        }

        $rows = Db::getInstance()->executeS($sql);
        if ($type == 'order') {
            foreach ($rows as &$row) {
                $row['attachments'] = ModelMpNoteOrderFile::getAttachments($row['id_mp_note_order']);
            }
        }
        if ($type == 'embroidery') {
            foreach ($rows as &$row) {
                $row['link'] = Context::getContext()->link->getAdminLink('AdminOrders', true, [], ['id_order' => $row['id_order'], 'vieworder' => 1]);
                $row['attachments'] = ModelMpNoteEmbroideryFile::getAttachments($row['id_mp_note_embroidery']);
            }
        }

        return $rows;
    }
}
