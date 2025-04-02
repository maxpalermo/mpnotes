<?php

use MpSoft\MpNotes\Helpers\CreateTemplate;

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
require_once dirname(__FILE__) . '/models/autoload.php';

use MpSoft\MpNotes\Helpers\NoteManager;
use MpSoft\MpNotes\Helpers\TableGenerator;

class MpNotes extends Module
{
    public function __construct()
    {
        $this->name = 'mpnotes';
        $this->tab = 'administration';
        $this->version = '2.1.0';
        $this->author = 'Massimiliano Palermo';
        $this->need_instance = 0;
        $this->bootstrap = true;
        $this->ps_versions_compliancy = [
            'min' => '8.0.0',
            'max' => _PS_VERSION_,
        ];

        parent::__construct();

        $this->displayName = $this->trans('MP Notes', [], 'Modules.Mpnotes.Admin');
        $this->description = $this->trans('A module to add notes to various PrestaShop entities', [], 'Modules.Mpnotes.Admin');
    }

    public function install()
    {
        return parent::install()
            && $this->registerHook('actionAdminControllerSetMedia')
            && $this->registerHook('displayAdminOrderMain')
            && $this->registerHook('displayAdminCustomers')
            && $this->registerHook('displayAdminEndContent')
            && $this->registerHook('actionOrderGridDefinitionModifier')
            && $this->registerHook('actionOrderGridQueryBuilderModifier')
            && (new TableGenerator())->createTable(ModelMpNote::$definition)
            && (new TableGenerator())->createTable(ModelMpNoteAttachment::$definition)
            && (new TableGenerator())->createTable(ModelMpNoteFlag::$definition);
    }

    public function uninstall()
    {
        return parent::uninstall();
    }

    public function renderWidget($hookName, array $configuration)
    {
        switch ($hookName) {
            case 'displayAdminOrderMain':
            case 'displayAdminOrderSide':
            case 'displayAdminOrderTop':
            case 'displayBackOfficeFooter':
                break;
            default:
                return '';
        }

        return '';
    }

    public function getWidgetVariables($hookName, array $configuration)
    {
        $vars = [];
        switch ($hookName) {
            case 'displayAdminOrderMain':
            case 'displayAdminOrderSide':
            case 'displayAdminOrderTop':
            case 'displayBackOfficeFooter':
                break;
            default:
                return [];
        }

        return $vars;
    }

    public function getContent()
    {
        $template = new CreateTemplate($this->name);
        $table = ModelMpNoteFlag::getTableTemplate();

        $params = [
            'link' => $this->context->link,
            'moduleName' => $this->name,
            'frontController' => $this->context->link->getModuleLink($this->name, 'Config'),
            'table' => $table['html'] ?? '',
            'icons' => ModelMpNoteFlag::getMaterialIconsList(),
        ];
        $html = $template->createTemplate('configuration.tpl', $params);

        return $html;
    }

    public function hookActionAdminControllerSetMedia()
    {
        $controller = Tools::strtolower(Tools::getValue('controller'));
        $id_order = (int) Tools::getValue('id_order');
        $id_customer = (int) Tools::getValue('id_customer');
        $id_employee = (int) Context::getContext()->employee->id;

        $allowControllers = [
            'adminorders',
            'admincustomers',
            'adminmodules',
        ];

        if (in_array($controller, $allowControllers)) {
            $this->context->controller->addCSS([
                $this->_path . 'views/js/sweetalert2.all.min.css',
                $this->_path . 'views/css/style.css',
            ], 'all', 100000);
            $this->context->controller->addJS([
                $this->_path . 'views/js/sweetalert2.all.min.js',
                $this->_path . 'views/js/popper-core2.js',
                $this->_path . 'views/js/tippy.js',
                $this->_path . 'views/js/swalBoxes/SwalConfirm.js',
                $this->_path . 'views/js/swalBoxes/SwalError.js',
                $this->_path . 'views/js/swalBoxes/SwalInput.js',
                $this->_path . 'views/js/swalBoxes/SwalSuccess.js',
                $this->_path . 'views/js/swalBoxes/SwalWarning.js',
                $this->_path . 'views/js/swalBoxes/SwalNote.js',
            ]);
        }

        if ($controller == 'adminorders' && $id_order) {
            $this->context->controller->addJS([
                $this->_path . 'views/js/summaryPanel/summaryPanel.js',
                $this->_path . 'views/js/notePanel/notePanel.js',
                $this->_path . 'views/js/notePanel/notePanelAttachment.js',
                $this->_path . 'views/js/notePanel/bindNoteAttachment.js',
                $this->_path . 'views/js/notePanel/bindNoteFlags.js',
                $this->_path . 'views/js/notePanel/bindSearchBar.js',
            ]);
        }

        if ($controller == 'admincustomers' && $id_customer) {
            $this->context->controller->addJS([
                $this->_path . 'views/js/summaryPanel/summaryPanel.js',
                $this->_path . 'views/js/notePanel/notePanel.js',
                $this->_path . 'views/js/notePanel/notePanelAttachment.js',
                $this->_path . 'views/js/notePanel/bindNoteAttachment.js',
                $this->_path . 'views/js/notePanel/bindNoteFlags.js',
                $this->_path . 'views/js/notePanel/bindSearchBar.js',
            ]);
        }

        if ($controller === 'adminmodules') {
            $this->context->controller->addJS([
                $this->_path . 'views/js/asyncOperation/ProgressData.js',
                $this->_path . 'views/js/asyncOperation/AsyncOperationClient.js',
                $this->_path . 'views/js/asyncOperation/ProgressManager.js',
                $this->_path . 'views/js/asyncOperation/ProgressOperation.js',
            ]);
        }
    }

    public function hookActionOrderGridDefinitionModifier($params)
    {
        /** @var \PrestaShop\PrestaShop\Core\Grid\Definition\GridDefinition $definition */
        $definition = $params['definition'];

        // Aggiungi una nuova colonna per mostrare il numero di note per ordine
        $definition->getColumns()->addAfter(
            'payment',
            (new \PrestaShop\PrestaShop\Core\Grid\Column\Type\Common\HtmlColumn('notes_count'))
                ->setName($this->trans('Note', [], 'Modules.Mpnotes.Admin'))
                ->setOptions([
                    'field' => 'notes_count',
                    'alignment' => 'center', // Centra il contenuto della colonna
                    'sortable' => true, // Abilita l'ordinamento
                ])
        );
    }

    public function hookActionOrderGridQueryBuilderModifier($params)
    {
        /** @var \Doctrine\DBAL\Query\QueryBuilder $searchQueryBuilder */
        $searchQueryBuilder = $params['search_query_builder'];

        // Verifica che la tabella mp_note esista prima di aggiungere la subquery
        $tableExists = \Db::getInstance()->executeS(
            'SHOW TABLES LIKE \'' . _DB_PREFIX_ . 'mp_note\''
        );

        if (!empty($tableExists)) {
            // Usa direttamente la subquery nel calcolo per evitare problemi con gli alias
            $noteCountSubquery = '(SELECT COUNT(id_mp_note) FROM `ps_mp_note` n WHERE ((n.id_customer = o.id_customer OR n.id_order = o.id_order) AND n.deleted = 0)) as notes_count';

            // Formatta il conteggio come HTML con badge
            $span = '<div class=\"text-center\"><span class=\"badge\" style=\"background-color: #007bff; color: #fcfcfc; padding: 6px; font-size: .90rem; border-radius: 50%;min-width: 24px; min-height: 24px;\">';
            $searchQueryBuilder->addSelect($noteCountSubquery);
        } else {
            // Se la tabella non esiste, aggiungi comunque il campo ma con valore 0
            $searchQueryBuilder->addSelect("'0' AS notes_count");
        }
    }

    public function hookDisplayAdminOrderMain($params)
    {
        // TODO
    }

    public function hookDisplayAdminCustomers($params)
    {
        // TODO
    }

    public function hookDisplayAdminEndContent($params)
    {
        $controller = Tools::strtolower(Tools::getValue('controller'));
        $orderId = (int) Tools::getValue('id_order');
        $customerId = (int) Tools::getValue('id_customer');
        $html = '';
        $script = '';

        if ($controller == 'adminorders' && $orderId) {
            $order = new \Order($orderId);
            if (!Validate::isLoadedObject($order)) {
                return '';
            }
            $id_order = (int) $order->id;
            $id_customer = (int) $order->id_customer;
            $html = (new NoteManager())->render($id_order, $id_customer, ['show_on_order_page' => true]);
            $script = (new CreateTemplate($this->name))
                ->createTemplate(
                    'AdminOrders/script.tpl',
                    [
                        'ajaxController' => $this->context->link->getModuleLink($this->name, 'Notes'),
                        'id_employee' => (int) $this->context->employee->id,
                    ]
                );
        } elseif ($controller == 'admincustomers' && $customerId) {
            $customer = new \Customer($customerId);
            if (!Validate::isLoadedObject($customer)) {
                return '';
            }
            $id_customer = (int) $customer->id;
            $html = (new NoteManager())->render(0, $id_customer, ['show_on_customer_page' => true]);
            $script = (new CreateTemplate($this->name))
                ->createTemplate(
                    'AdminCustomers/script.tpl',
                    [
                        'ajaxController' => $this->context->link->getModuleLink($this->name, 'Notes'),
                        'id_employee' => (int) $this->context->employee->id,
                    ]
                );
        }

        return $script . $html;
    }

    public function getNotes($entity_type, $entity_id)
    {
        return Db::getInstance()->executeS('
            SELECT n.*, e.firstname, e.lastname
            FROM `' . _DB_PREFIX_ . 'mpnotes` n
            LEFT JOIN `' . _DB_PREFIX_ . 'employee` e ON (n.id_employee = e.id_employee)
            WHERE n.entity_type = "' . pSQL($entity_type) . '"
            AND n.entity_id = ' . (int) $entity_id . '
            ORDER BY n.date_add DESC
        ');
    }
}
