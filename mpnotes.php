<?php

use MpSoft\MpNotes\Helpers\CurlExec;
use MpSoft\MpNotes\Helpers\GetTwigEnvironment;
use MpSoft\MpNotes\Models\ModelMpNote;
use MpSoft\MpNotes\Models\ModelMpNoteAttachment;
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
        $this->version = '2.2.56';
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

    protected static function getTabRepository()
    {
        $tabRepository = SymfonyContainer::getInstance()->get('prestashop.core.admin.tab.repository');

        return $tabRepository;
    }

    protected function installTab()
    {
        $parentClass = 'AdminOtherModulesMp';
        $tabRepository = static::getTabRepository();
        $sellId = (int) $tabRepository->findOneIdByClassName('SELL');
        $parentId = (int) $tabRepository->findOneIdByClassName($parentClass);

        if (!$parentId) {
            $parentTab = new Tab();
            $parentTab->class_name = $parentClass;
            $parentTab->module = $this->name;
            $parentTab->id_parent = $sellId;
            $parentTab->active = 1;
            $parentTab->icon = 'extension';
            foreach (Language::getLanguages() as $language) {
                $parentTab->name[$language['id_lang']] = $this->l('ALTRI MODULI');
            }
            $parentTab->add();
            $parentId = (int) $parentTab->id;
        }

        $childClass = 'AdminMpNotes';
        $childId = $tabRepository->findOneIdByClassName($childClass);
        $tab = $childId ? new Tab($childId) : new Tab();
        $tab->class_name = $childClass;
        $tab->module = $this->name;
        $tab->id_parent = $parentId;
        $tab->active = 1;
        $tab->icon = 'icon-note';
        foreach (Language::getLanguages() as $language) {
            $tab->name[$language['id_lang']] = $this->l('Messaggi');
        }

        return $childId ? $tab->update() : $tab->add();
    }

    public function uninstall()
    {
        return parent::uninstall() &&
            $this->uninstallTab();
    }

    protected function uninstallTab()
    {
        $tabRepository = static::getTabRepository();
        $childId = $tabRepository->findOneIdByClassName('AdminMpNotes');
        if ($childId) {
            $tab = new Tab($childId);
            $tab->delete();
        }

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
        if (Tools::isSubmit('submitButton')) {
            switch (Tools::getValue('submitButton')) {
                case 'save':
                    $endpoint = Tools::getValue('endpoint');
                    $connector_token = Tools::getValue('connector_token');
                    Configuration::updateValue('MPCONNECTOR_ENDPOINT', $endpoint);
                    Configuration::updateValue('MPCONNECTOR_TOKEN', $connector_token);
                    $this->_confirmations[] = $this->l('Impostazioni salvate');
                    break;
                case 'truncate':
                    $this->truncateTables();
                    $this->_confirmations[] = $this->l('Tabelle svuotate');
            }
        }

        $path = $this->getLocalPath() . 'views/templates/admin/';
        $template = $this->context->smarty->createTemplate("{$path}configuration.tpl");
        $params = [
            'link' => $this->context->link,
            'moduleName' => $this->name,
            'adminControllerUrl' => $this->context->link->getAdminLink('AdminMpNotes'),
            'table' => $table['html'] ?? '',
            'icons' => [],
            'endpoint' => Configuration::get('MPCONNECTOR_ENDPOINT'),
            'connector_token' => Configuration::get('MPCONNECTOR_TOKEN'),
            'curl' => $this->getRecordCount(),
            'flash' => $this->_confirmations,
        ];
        $template->assign($params);

        $html = $template->fetch();

        return $html;
    }

    private function getRecordCount()
    {
        $endpoint = \Configuration::get('MPCONNECTOR_ENDPOINT');
        $token = \Configuration::get('MPCONNECTOR_TOKEN');
        $action = 'setQuery';

        if ($endpoint && $token) {
            return [
                'MpCustomerArchive' => CurlExec::exec($endpoint, $action, 'select count(*) as total from ps_customer_archive', $token, 'embroidery', '#customer_archive'),
                'MpCustomerArchiveItem' => CurlExec::exec($endpoint, $action, 'select count(*) as total from ps_customer_archive_item', $token, 'embroidery', '@customer_archive_item'),
                'MpCustomerMessages' => CurlExec::exec($endpoint, $action, 'select count(*) as total from ps_customer_messages', $token, 'customer', '#customer_messages'),
                'MpCustomerPrivateNote' => CurlExec::exec($endpoint, $action, 'select count(*) as total from ps_customer where `note` is not null', $token, 'customer', '#customer'),
                'MpCustomerOrderNotes' => CurlExec::exec($endpoint, $action, 'select count(*) as total from ps_mp_customer_order_notes', $token, 'order', '#mp_customer_order_notes'),
                'MpCustomerOrderNotesAttachments' => CurlExec::exec($endpoint, $action, 'select count(*) as total from ps_mp_customer_order_notes_attachments', $token, 'order', '@mp_customer_order_notes_attachments'),
            ];
        }

        return false;
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
        $twig = (new GetTwigEnvironment($this->name));
        $twig->load('@ModuleTwig/admin/customers/bsTableCustomerMessages.html.twig');

        return $twig->render([
            'endpoint' => $this->context->link->getAdminLink('AdminMpNotes'),
            'id_customer' => (int) $params['id_customer'],
            'isAdmin' => $this->context->employee->isSuperAdmin()
        ]);
    }

    public function truncateTables()
    {
        $tables = [
            'mpnote',
            'mpnote_attachment',
        ];

        foreach ($tables as $table) {
            $table = _DB_PREFIX_ . $table;
            $sql = "TRUNCATE TABLE {$table}";

            Db::getInstance()->execute($sql);
        }

        return [
            'success' => true,
            'message' => 'Tabelle troncate con successo',
        ];
    }
}
