<?php

use MpSoft\MpNotes\Helpers\GetTwigEnvironment;
use MpSoft\MpNotes\Helpers\ImportFromV16;
use MpSoft\MpNotes\Models\ModelMpNote;

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
class AdminMpNotesController extends ModuleAdminController
{
    public function __construct()
    {
        $this->bootstrap = true;
        parent::__construct();

        if (Tools::isSubmit('ajax') && Tools::isSubmit('action')) {
            $action = 'ajaxProcess' . Tools::ucfirst(Tools::getValue('action'));
            if (method_exists($this, $action)) {
                $this->response($this->$action());
            }
        }
    }

    protected function response($data, $status = 200)
    {
        header('Content-Type: application/json');
        http_response_code($status);
        echo json_encode($data);

        exit;
    }

    public function initContent()
    {
        $twig = new GetTwigEnvironment($this->module->name);
        $template = $twig->load('@ModuleTwig/admin/Admin.page.html.twig');
        $params = [
            'adminControllerUrl' => $this->context->link->getAdminLink('AdminMpNotes'),
        ];
        $this->content = $template->render($params);

        parent::initContent();
    }

    public function ajaxProcessFetchNoteByTypeAndIdOrder()
    {
        $type = Tools::getValue('type');
        $id_order = Tools::getValue('id_order');
        $search = Tools::getValue('search');

        $notes = ModelMpNote::getNotesByTypeAndIdOrder($id_order, $type, $search);
        $this->response([
            'success' => true,
            'data' => $notes
        ]);
    }

    public function ajaxProcessFetchAllNotes()
    {
        $params = [
            'search' => Tools::getValue('search'),
            'id_order' => Tools::getValue('orderId'),
            'orderBy' => Tools::getValue('sort'),
            'sort' => Tools::getValue('order'),
            'limit' => Tools::getValue('limit'),
            'offset' => Tools::getValue('offset'),
            'type' => Tools::getValue('type'),
        ];

        $data = ModelMpNote::getNotesByType($params);

        $this->response([
            'success' => true,
            'rows' => $data['rows'],
            'total' => $data['total'],
            'totalNotFiltered' => $data['totalNotFiltered'],
            'offset' => $data['offset'],
            'limit' => $data['limit'],
            'query' => $data['query']
        ]);
    }

    public function ajaxProcessTruncateTables()
    {
        $importer = new ImportFromV16($this->module);
        $importer->truncate();

        $this->response([
            'success' => true,
        ]);
    }

    public function ajaxProcessUpdateNote()
    {
        $params = [
            'type' => Tools::getValue('DialogMpNote-Type'),
            'id' => (int) Tools::getValue('DialogMpNote-Id'),
            'id_order' => (int) Tools::getValue('DialogMpNote-OrderId'),
            'id_customer' => (int) Tools::getValue('DialogMpNote-CustomerId'),
            'content' => Tools::getValue('DialogMpNote-Content'),
            'printable' => (int) Tools::getValue('DialogMpNote-Printable'),
            'chat' => (int) Tools::getValue('DialogMpNote-Chat'),
            'gravity' => Tools::getValue('DialogMpNote-Gravity', 'info'),
        ];

        $id_employee = (int) Context::getContext()->employee->id;
        $employee = new Employee($id_employee);
        $params['employee_firstname'] = $employee->firstname;
        $params['employee_lastname'] = $employee->lastname;

        $note = new ModelMpNote($params['id']);
        $note->hydrate($params);
        if (Validate::isLoadedObject($note)) {
            $note->date_upd = date('Y-m-d H:i:s');
            $result = $note->update();
        } else {
            $note->date_add = date('Y-m-d H:i:s');
            $note->date_upd = null;
            $result = $note->add(false, true);
        }

        $this->response([
            'success' => $result,
            'id' => $note->id,
        ]);
    }

    public function ajaxProcessGetAttachmentsPreview()
    {
        $id_mpnote = (int) Tools::getValue('id_mpnote');
        $type = Tools::getValue('type');

        $db = Db::getInstance();
        $sql = new DbQuery();
        $sql
            ->select('mp_customer_order_notes_attachments.*')
            ->from('mp_customer_order_notes_attachments')
            ->where('id_mpnote = ' . $id_mpnote)
            ->where('type = ' . $type);

        $attachments = $db->executeS($sql);
        $src = [];
        $base = _PS_DOWNLOAD_DIR_ . 'mpnotes/';
        if ($attachments) {
            foreach ($attachments as $attachment) {
                $src[] = [
                    'title' => $attachment['filetitle'] ?: $attachment['filename'],
                    'filename' => $base . $attachment['filename'],
                    'type' => $attachment['type'],
                ];
            }
        }

        $twig = new GetTwigEnvironment($this->module->name);
        $twig->load('@ModuleTwig/admin/attachments-preview.html.twig');
        $html = $twig->render($src);

        return [
            'success' => true,
            'html' => $html
        ];
    }

    public function ajaxProcessGetNoteDetails()
    {
        $id = (int) Tools::getValue('id');
        $details = ModelMpNote::getNoteDetails($id);

        $this->response([
            'success' => true,
            'data' => $details,
        ]);
    }

    public function ajaxProcessGetTotalRows()
    {
        $tableName = Tools::getValue('tableName');
        $importer = new ImportFromV16($this->module);
        $totalRows = (int) $importer->getCountRows($tableName);

        $this->response([
            'success' => true,
            'totalRows' => $totalRows
        ]);
    }

    public function ajaxProcessImportV16()
    {
        $limit = (int) Tools::getValue('limit');
        $offset = (int) Tools::getValue('offset');
        $table = Tools::getValue('table');
        $importer = new ImportFromV16($this->module);
        $attachments = (int) Tools::getValue('attachments');

        switch ($table) {
            case 'customer_messages':
                $data = $importer->getDataCustomerMessages($limit, $offset);
                break;
            case 'mp_customer_order_notes':
            case 'mp_customer_order_notes_attachments':
                $data = $importer->getDataOrderMessages($limit, $offset, $attachments);
                break;
            case 'customer_archive':
            case 'customer_archive_item':
                $data = $importer->getDataEmbroideryMessages($limit, $offset, $attachments);
                break;
            default:
                $data = [
                    'success' => false,
                    'message' => 'Tabella non valida'
                ];
        }

        if (!is_array($data)) {
            $data = [];
        }

        $offset += count($data) < $limit ? count($data) : $limit;
        $import = $importer->doImport($data, $attachments);

        if ($import == true) {
            $this->response([
                'success' => true,
                'errors' => json_encode([]),
                'offset' => $offset,
                'limit' => $limit,
                'end_of_data' => true,
            ]);
        }

        $this->response([
            'success' => count($import) ? false : true,
            'errors' => json_encode($import),
            'offset' => $offset,
            'limit' => $limit,
            'end_of_data' => false,
        ]);
    }

    protected function toggleField($id, $field)
    {
        $employee = $this->context->employee;

        if (!$id) {
            return [
                'success' => false,
                'message' => "Proprietà {$field} - Id non valida.",
            ];
        }

        $db = Db::getInstance();
        $pfx = _DB_PREFIX_;
        $table = $pfx . ModelMpNote::$definition['table'];
        $date = date('Y-m-d H:i:s');
        $employee_id = (int) $employee->id;
        $employee_firstname = Tools::strtoupper($employee->firstname);
        $employee_lastname = Tools::strtoupper($employee->lastname);

        try {
            $result = $db->execute("
                UPDATE
                    {$table}
                SET
                    {$field} = IF({$field} = 0, 1, 0),
                    date_upd = '{$date}',
                    id_employee = {$employee_id},
                    employee_firstname = '{$employee_firstname}',
                    employee_lastname = '{$employee_lastname}'
                WHERE
                    id_mpnote = {$id}
            ");
        } catch (\Throwable $th) {
            return [
                'success' => false,
                'message' => $db->getMsgError(),
            ];
        }

        if ($result) {
            return [
                'success' => true,
                'message' => "Proprietà {$field} aggiornata.",
            ];
        }

        return [
            'success' => false,
            'message' => $db->getMsgError(),
        ];
    }

    public function ajaxProcessToggleAction()
    {
        $idNote = (int) Tools::getValue('idNote');
        $action = Tools::getValue('toggleAction');

        if ($action) {
            $field = trim(Tools::strtolower(str_replace('toggle', '', $action)));
        }

        return $this->toggleField($idNote, $field);
    }
}
