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

use MpSoft\MpNotes\Helpers\GetTwigEnvironment;
use MpSoft\MpNotes\Helpers\NoteManager;
use MpSoft\MpNotes\Helpers\TableGenerator;
use MpSoft\MpNotes\Models\ModelMpNote;
use MpSoft\MpNotes\Models\ModelMpNoteAttachment;
use MpSoft\MpNotes\Models\ModelMpNoteFlag;
use MpSoft\MpNotes\Traits\ModuleTraits;
use PrestaShop\PrestaShop\Adapter\SymfonyContainer;
use PrestaShop\PrestaShop\Core\Module\WidgetInterface;

class MpNotes extends Module implements WidgetInterface
{
    use ModuleTraits;

    public function __construct()
    {
        $this->name = 'mpnotes';
        $this->tab = 'administration';
        $this->version = '2.2.36';
        $this->author = 'Massimiliano Palermo';
        $this->need_instance = 0;
        $this->bootstrap = true;
        $this->ps_versions_compliancy = [
            'min' => '8.1.0',
            'max' => '8.99.99',
        ];

        parent::__construct();

        $this->displayName = $this->trans('MP Gestione note', [], 'Modules.Mpnotes.Admin');
        $this->description = $this->trans('Gestisci le note per vari tipi di messaggio', [], 'Modules.Mpnotes.Admin');
    }

    public function install()
    {
        return parent::install() &&
            $this->registerHook([
                'actionAdminControllerSetMedia',
                'actionOrderGridDefinitionModifier',
                'actionOrderGridQueryBuilderModifier',
                'actionOrderGridDataModifier',
                'displayAdminOrderTop',
                'displayAdminCustomers',
            ]) &&
            ModelMpNote::install() &&
            ModelMpNoteAttachment::install() &&
            $this->installTab();
    }

    protected function installTab()
    {
        $tabRepository = SymfonyContainer::getInstance()->get('prestashop.core.admin.tab.repository');
        $id_ParentCustomer = $tabRepository->findOneIdByClassName('AdminParentCustomer');

        $tab = new Tab();
        $tab->class_name = 'AdminMpNotes';
        $tab->module = $this->name;
        $tab->id_parent = (int) $id_ParentCustomer;
        $tab->icon = 'icon-note';
        foreach (Language::getLanguages() as $language) {
            $tab->name[$language['id_lang']] = $this->l('MP Note clienti');
        }

        return $tab->add();
    }

    public function uninstall()
    {
        return parent::uninstall() &&
            $this->uninstallTab();
    }

    protected function uninstallTab()
    {
        $tabRepository = SymfonyContainer::getInstance()->get('prestashop.core.admin.tab.repository');
        $id_tab = $tabRepository->findOneIdByClassName('AdminMpNotes');
        $tab = new Tab($id_tab);

        $tab->delete();

        return true;
    }

    public function renderWidget($hookName, array $configuration)
    {
        switch ($hookName) {
            case 'displayAdminOrderMain':
                break;
            case 'displayAdminOrderSide':
                break;
            case 'displayAdminOrderTop':
                return $this->displayAdminOrderTop($configuration);
            case 'displayBackOfficeFooter':
                break;
            case 'displayAdminEndContent':
                break;
        }

        return '';
    }

    protected function displayAdminOrderTop($configuration)
    {
        $customerId = 0;
        $customerName = '';

        if (isset($configuration['id_order'])) {
            $id_order = (int) $configuration['id_order'];
        } else {
            $id_order = (int) Tools::getValue('id_order', 0);
        }
        $id_customer = 0;

        if ($id_order) {
            $order = new Order($id_order);
            $customer = new Customer($order->id_customer);

            $customer = [
                'id' => (int) $order->id_customer,
                'name' => Tools::ucwords($customer->firstname . ' ' . $customer->lastname),
            ];
        }

        $id_employee = (int) $this->context->employee->id;
        $employeeName = Tools::ucwords($this->context->employee->firstname . ' ' . $this->context->employee->lastname);

        $params = [
            'endpoint' => $this->context->link->getAdminLink('AdminMpNotes'),
            'orderLinkUrl' => $this->context->link->getAdminLink('AdminOrders', true, [], ['id_order' => '0', 'vieworder' => 1]),
            'orderId' => $order->id,
            'customer' => $customer,
            'employee' => [
                'id' => $id_employee,
                'name' => $employeeName
            ],
            'navTabs' => [
                'customer' => [
                    'id' => ModelMpNote::TYPE_CUSTOMER,
                    'type' => 'customer',
                    'enabled' => true,
                    'notes' => ModelMpNote::getNotesCountByTypeAndIdOrder($id_order, 'customer'),
                    'label' => 'Clienti',
                    'icon' => 'person',
                    'color' => 'var(--info)'
                ],
                'order' => [
                    'id' => ModelMpNote::TYPE_ORDER,
                    'type' => 'order',
                    'enabled' => true,
                    'notes' => ModelMpNote::getNotesCountByTypeAndIdOrder($id_order, 'order'),
                    'label' => 'Ordini',
                    'icon' => 'shopping_cart',
                    'color' => 'var(--info)'
                ],
                'embroidery' => [
                    'id' => ModelMpNote::TYPE_EMBROIDERY,
                    'type' => 'embroidery',
                    'enabled' => true,
                    'notes' => ModelMpNote::getNotesCountByTypeAndIdOrder($id_order, 'embroidery'),
                    'label' => 'Ricami',
                    'icon' => 'timeline',
                    'color' => 'var(--info)'
                ],
            ],
        ];
        $path = '@ModuleTwig/admin/AdminOrder.html.twig';

        $template = (new GetTwigEnvironment($this->name))->load($path);
        $html = $template->render($params);

        return $html;
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
        $template = $this->context->smarty->createTemplate('configuration.tpl');
        $params = [
            'link' => $this->context->link,
            'moduleName' => $this->name,
            'frontController' => $this->context->link->getModuleLink($this->name, 'Config'),
            'table' => $table['html'] ?? '',
            'icons' => [],
        ];
        $template->assign($params);

        $html = $template->fetch();

        return $html;
    }

    public function hookActionAdminControllerSetMedia($params)
    {
        $controller = Tools::getValue('controller');
        $id_order = (int) Tools::getValue('id_order');

        if (preg_match('/^AdminOrders/i', $controller) && $id_order) {
            $path = $this->getLocalPath() . 'views/assets/';
            $this->context->controller->addCSS("{$path}css/style.css");
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
                    'alignment' => 'center',  // Centra il contenuto della colonna
                    'sortable' => false,  // Abilita l'ordinamento
                ])
        );
    }

    public function hookActionOrderGridQueryBuilderModifier($params)
    {
        /** @var \Doctrine\DBAL\Query\QueryBuilder $searchQueryBuilder */
        $searchQueryBuilder = $params['search_query_builder'];

        // Aggiungi un valore fittizio per notes_count che verrà popolato nel GridDataModifier
        $searchQueryBuilder->addSelect("'0' AS notes_count");
    }

    public function hookActionOrderGridDataModifier($params)
    {
        $records = $params['data']->getRecords()->all();
        $table = _DB_PREFIX_ . ModelMpNote::$definition['table'];

        foreach ($records as &$record) {
            $idOrder = $record['id_order'];
            $idCustomer = $record['id_customer'];

            $rows = Db::getInstance()->executeS("
                SELECT
                    'person' as icon,
                    COUNT(id_mpnote) as total_notes
                FROM
                    `{$table}`
                WHERE (type='customer' and id_customer={$idCustomer})
                AND deleted = 0

                UNION

                SELECT
                    'shopping_cart' as icon,
                    COUNT(id_mpnote) as total_notes
                FROM
                    `{$table}`
                WHERE (type='order' and id_order={$idOrder})
                AND deleted = 0

                UNION 

                SELECT
                    'timeline' as icon,
                    COUNT(id_mpnote) as total_notes
                FROM
                    `{$table}`
                WHERE (type='embroidery' and id_customer={$idCustomer})
                AND deleted = 0
            ");

            if ($rows) {
                $twig = new GetTwigEnvironment($this->name);
                $twig->load('@ModuleTwig/admin/orders/columns/countTypeMessages.html.twig');
                $html = $twig->render(['rows' => $rows]);
            } else {
                $html = '--';
            }

            $record['notes_count'] = $html;
        }

        // Ricrea la collection con i dati modificati
        $recordCollection = new PrestaShop\PrestaShop\Core\Grid\Record\RecordCollection($records);
        $data = new PrestaShop\PrestaShop\Core\Grid\Data\GridData(
            $recordCollection,
            $params['data']->getRecordsTotal(),
            $params['data']->getQuery()
        );

        // Assegna i dati modificati al parametro (passato per riferimento)
        $params['data'] = $data;
    }

    public function hookDisplayAdminCustomers($params)
    {
        // TODO
    }

    public function hookDisplayAdminOrderTop2($params)
    {
        $path = $this->getLocalPath() . 'views/twig/test-nav.html.twig';
        $template = (new GetTwigEnvironment($this->name))->load($path);
        $html = $template->render();

        return $html;

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
                        'noteControllerUrl' => $this->getAdminLink('admin_note_controller'),
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

    public function hookDisplayAdminEndContent($params)
    {
        // todo
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

    public function getAdminLink($controller, $method = 'index')
    {
        try {
            $router = SymfonyContainer::getInstance()->get('router');
            $routeName = 'mpnotes_' . strtolower($controller) . '_' . strtolower($method ?? 'index');

            // Verifica se la route esiste
            $routeCollection = $router->getRouteCollection();
            if ($routeCollection && $routeCollection->get($routeName)) {
                $url = $router->generate($routeName);
                return $url;
            } else {
                // Fallback per compatibilità
                return $this->context->link->getAdminLink($controller);
            }
        } catch (Exception $e) {
            // Fallback in caso di errore
            return null;
        }
    }
}
