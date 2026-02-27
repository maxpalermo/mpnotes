<?php

use MpSoft\MpNotes\Helpers\CurlExec;
use MpSoft\MpNotes\Helpers\GetTwigEnvironment;
use MpSoft\MpNotes\Helpers\ImportFromV16;
use MpSoft\MpNotes\Models\ModelMpNote;
use MpSoft\MpNotes\Models\ModelMpNoteAttachment;
use Tools;

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
            'endpoint' => $this->context->link->getAdminLink('AdminMpNotes'),
            'orderId' => 0,
            'customer' => [
                'id' => 0,
                'name' => ''
            ],
            'employee' => [
                'id' => $this->context->employee->id,
                'name' => Tools::ucwords($this->context->employee->firstname . ' ' . $this->context->employee->lastname)
            ],
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

    public function ajaxProcessFetchAdminAllNotes()
    {
        $params = [
            'search' => Tools::getValue('search'),
            'id_order' => (int) Tools::getValue('orderId'),
            'orderBy' => Tools::getValue('sort'),
            'sort' => Tools::getValue('order'),
            'limit' => (int) Tools::getValue('limit'),
            'offset' => (int) Tools::getValue('offset'),
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

    /**
     * Mostra il pannello della nota cliente
     * Chiamata dal metodo AJAX getCustomerNotePanel
     * @return void
     */
    public function ajaxProcessGetCustomerNotePanel()
    {
        $id_customer = (int) Tools::getValue('id_customer');
        $id_mpnote = (int) Tools::getValue('id_mpnote', 0);
        $content = Tools::getValue('content', '');

        if ($id_mpnote) {
            $model = new ModelMpNote($id_mpnote);
            if (Validate::isLoadedObject($model)) {
                $id_customer = $model->id_customer;
                $content = $model->content;
            }
        }

        $twig = new GetTwigEnvironment($this->module->name);
        $twig->load('@ModuleTwig/admin/customers/partials/note.html.twig');

        $html = $twig->render([
            'id_customer' => $id_customer,
            'id_mpnote' => $id_mpnote,
            'content' => $content,
        ]);

        $this->response([
            'success' => true,
            'html' => $html,
        ]);
    }

    public function ajaxProcessGetNoteDetails()
    {
        $idNote = (int) Tools::getValue('idNote');
        $details = ModelMpNote::getNoteDetails($idNote);

        $this->response([
            'success' => true,
            'data' => $details,
        ]);
    }

    public function ajaxProcessGetCustomerNotes()
    {
        $db = Db::getInstance();
        $id_customer = (int) Tools::getValue('id_customer');
        $sql = new DbQuery();
        $sql
            ->select('*')
            ->from('mpnote')
            ->where("id_customer = {$id_customer}")
            ->where("type='customer'")
            ->orderBy('date_add DESC, id_mpnote DESC');
        $notes = $db->executeS($sql);

        $twig = new GetTwigEnvironment($this->module->name);
        $template = $twig->load('@ModuleTwig/admin/customers/partials/adminCustomerTableNote.html.twig');
        $html = $template->render([
            'notes' => $notes,
        ]);

        $this->response([
            'success' => true,
            'html' => $html,
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

    public function ajaxProcessAddCustomerNote()
    {
        $id_customer = (int) Tools::getValue('id_customer');
        $id_mpnote = (int) Tools::getValue('id_mpnote');
        $content = Tools::getValue('content');
        $id_employee = (int) $this->context->employee->id;
        $employee = new Employee($id_employee);
        $employee_firstname = Tools::ucwords($employee->firstname);
        $employee_lastname = Tools::ucwords($employee->lastname);

        if (!$id_customer) {
            $this->response([
                'success' => false,
                'message' => 'ID cliente non valido',
            ]);
        }

        $customer = new Customer($id_customer);
        $customer_firstname = Tools::ucwords($customer->firstname);
        $customer_lastname = Tools::ucwords($customer->lastname);

        $note = new ModelMpNote($id_mpnote);
        $note->id_customer = $id_customer;
        $note->id_employee = $id_employee;
        $note->customer_firstname = $customer_firstname;
        $note->customer_lastname = $customer_lastname;
        $note->employee_firstname = $employee_firstname;
        $note->employee_lastname = $employee_lastname;
        $note->content = $content;
        $note->type = 'customer';
        $note->reference = 'mpnote';

        try {
            if (Validate::isLoadedObject($note)) {
                $note->date_upd = date('Y-m-d H:i:s');
                $result = $note->update();
            } else {
                $note->date_add = date('Y-m-d H:i:s');
                $result = $note->add(false, true);
            }

            $this->response([
                'success' => $result,
                'id' => $note->id,
            ]);
        } catch (\Throwable $th) {
            $this->response([
                'success' => false,
                'message' => $th->getMessage(),
            ]);
        }
    }

    public function ajaxProcessAddAttachment()
    {
        $type = Tools::getValue('DialogMpNoteAttachment-Type');
        $idNote = (int) Tools::getValue('DialogMpNoteAttachment-NoteId');
        $idOrder = (int) Tools::getValue('DialogMpNoteAttachment-OrderId');

        if (!$idOrder) {
            $idOrder = (int) Tools::getValue('id_order');
        }

        if (!$idNote) {
            return [
                'success' => false,
                'message' => 'ID nota non valido',
            ];
        }

        if (empty($_FILES) || !isset($_FILES['attachments'])) {
            return [
                'success' => false,
                'message' => 'Nessun file caricato',
            ];
        }

        $note = new ModelMpNote($idNote);
        if (!Validate::isLoadedObject($note)) {
            return [
                'success' => false,
                'message' => 'Nota non trovata',
            ];
        }

        $employeeCtx = Context::getContext()->employee;
        $idEmployee = (int) ($employeeCtx ? $employeeCtx->id : 0);
        $employee = $idEmployee ? new Employee($idEmployee) : null;

        $uploadDir = _PS_IMG_DIR_ . 'mpnotes/';
        if (!is_dir($uploadDir)) {
            @mkdir($uploadDir, 0755, true);
        }

        if (!is_dir($uploadDir) || !is_writable($uploadDir)) {
            return [
                'success' => false,
                'message' => 'Directory upload non scrivibile: ' . $uploadDir,
            ];
        }

        $files = $_FILES['attachments'];
        $fileCount = is_array($files['name'] ?? null) ? count($files['name']) : 0;
        if (!$fileCount) {
            return [
                'success' => false,
                'message' => 'Nessun file caricato',
            ];
        }

        $uploaded = [];
        $errors = [];

        for ($i = 0; $i < $fileCount; $i++) {
            $origName = (string) ($files['name'][$i] ?? '');
            $tmpName = (string) ($files['tmp_name'][$i] ?? '');
            $error = (int) ($files['error'][$i] ?? UPLOAD_ERR_NO_FILE);

            if ($error !== UPLOAD_ERR_OK) {
                $errors[] = "Errore upload {$origName} (code {$error})";
                continue;
            }

            $ext = strtolower((string) pathinfo($origName, PATHINFO_EXTENSION));
            $safeExt = preg_replace('/[^a-z0-9]/i', '', $ext);
            if (!$safeExt) {
                $safeExt = 'dat';
            }

            $newFileName = uniqid('mpnote_', true) . '.' . $safeExt;
            $target = $uploadDir . $newFileName;

            if (!move_uploaded_file($tmpName, $target)) {
                $errors[] = "Errore durante lo spostamento del file {$origName}";
                continue;
            }

            $attachment = new ModelMpNoteAttachment();
            $attachment->id_history = (int) ($note->id_history ?? 0);
            $attachment->type = $type ?: (string) ($note->type ?? '');
            $attachment->id_mpnote = (int) $note->id;
            $attachment->reference = (string) ($note->reference ?? '');
            $attachment->id_customer = (int) ($note->id_customer ?? 0);
            $attachment->id_order = (int) ($note->id_order ?: $idOrder);
            $attachment->id_employee = $idEmployee;
            $attachment->employee_firstname = $employee && Validate::isLoadedObject($employee) ? (string) $employee->firstname : '';
            $attachment->employee_lastname = $employee && Validate::isLoadedObject($employee) ? (string) $employee->lastname : '';
            $attachment->filename = $newFileName;
            $attachment->filetitle = $origName;
            $attachment->file_ext = $safeExt;
            $attachment->deleted = 0;
            $attachment->date_add = date('Y-m-d H:i:s');
            $attachment->date_upd = null;

            if ($attachment->add()) {
                $uploaded[] = [
                    'id_attachment' => (int) $attachment->id,
                    'title' => $attachment->filetitle,
                    'filename' => $attachment->filename,
                    'url' => Context::getContext()->link->getBaseLink() . 'img/mpnotes/' . $attachment->filename,
                ];
            } else {
                $errors[] = "Errore durante il salvataggio del record per {$origName}";
                if (file_exists($target)) {
                    @unlink($target);
                }
            }
        }

        if ($errors) {
            return [
                'success' => false,
                'message' => implode("\n", $errors),
                'uploaded' => $uploaded,
            ];
        }

        return [
            'success' => true,
            'message' => count($uploaded) . ' allegati caricati con successo',
            'uploaded' => $uploaded,
        ];
    }

    public function ajaxProcessDeleteNote()
    {
        $idMpNote = (int) Tools::getValue('id_mpnote');
        if ($idMpNote) {
            $model = new ModelMpNote($idMpNote);
            if (Validate::isLoadedObject($model)) {
                $res = $model->delete();
                $this->response([
                    'success' => $res,
                    'message' => $res ? 'Nota eliminata con successo' : "Errore durante l'eliminazione della nota",
                ]);
            }
        }

        $this->response([
            'success' => false,
            'message' => 'Nota non trovata',
        ]);
    }

    public function ajaxProcessDeleteAttachment()
    {
        $idAttachment = (int) Tools::getValue('id_attachment');
        if (!$idAttachment) {
            $idAttachment = (int) Tools::getValue('idAttachment');
        }
        if (!$idAttachment) {
            $idAttachment = (int) Tools::getValue('id');
        }

        if (!$idAttachment) {
            return [
                'success' => false,
                'message' => 'ID allegato non valido',
            ];
        }

        $attachment = new ModelMpNoteAttachment($idAttachment);
        if (!Validate::isLoadedObject($attachment)) {
            return [
                'success' => false,
                'message' => 'Allegato non trovato',
            ];
        }

        $filePath = _PS_IMG_DIR_ . 'mpnotes/' . ltrim((string) $attachment->filename, '/');
        if (file_exists($filePath)) {
            @unlink($filePath);
        }

        $result = (bool) $attachment->delete();

        return [
            'success' => $result,
            'message' => $result ? 'Allegato eliminato con successo' : "Errore durante l'eliminazione dell'allegato",
            'id_attachment' => $idAttachment,
        ];
    }

    public function ajaxProcessTruncateTables()
    {
        /** @var \MpNotes $module */
        return $this->module->truncateTables();
    }

    public function ajaxprocessImportV16()
    {
        $endpoint = \Configuration::get('MPCONNECTOR_ENDPOINT');
        $token = \Configuration::get('MPCONNECTOR_TOKEN');
        $offset = (int) Tools::getValue('offset', 0);
        $limit = (int) Tools::getValue('limit', 5000);
        $table = Tools::getValue('table');

        switch ($table) {
            case 'MpCustomerArchive':
                $list = CurlExec::getCustomerArchiveRecords($endpoint, $token, $limit, $offset);
                $importer = new ImportFromV16($this->module);
                $importer->importCustomerArchive($list['remote']);
                $listCount = (int) count($list['remote']);
                $offset += $listCount;

                return [
                    'success' => $list['success'],
                    'done' => $listCount ? false : true,
                    'offset' => $offset,
                    'limit' => $limit,
                    'imported' => $listCount,
                ];
            case 'MpCustomerArchiveItem':
                $list = CurlExec::getCustomerArchiveItemRecords($endpoint, $token, $limit, $offset);
                $importer = new ImportFromV16($this->module);
                $importer->importCustomerArchiveItem($list['remote']);
                $listCount = (int) count($list['remote']);
                $offset += $listCount;

                return [
                    'success' => $list['success'],
                    'done' => $listCount ? false : true,
                    'offset' => $offset,
                    'limit' => $limit,
                    'imported' => $listCount,
                ];
            case 'MpCustomerMessages':
                $list = CurlExec::getCustomerMessagesRecords($endpoint, $token, $limit, $offset);
                $importer = new ImportFromV16($this->module);
                $importer->importCustomerMessages($list['remote']);
                $listCount = (int) count($list['remote']);
                $offset += $listCount;

                return [
                    'success' => $list['success'],
                    'done' => $listCount ? false : true,
                    'offset' => $offset,
                    'limit' => $limit,
                    'imported' => $listCount,
                ];
            case 'MpCustomerPrivateNote':
                $list = CurlExec::getCustomerPrivateNoteRecords($endpoint, $token, $limit, $offset);
                $importer = new ImportFromV16($this->module);
                $importer->importCustomerPrivateNotes($list['remote']);
                $listCount = (int) count($list['remote']);
                $offset += $listCount;

                return [
                    'success' => $list['success'],
                    'done' => $listCount ? false : true,
                    'offset' => $offset,
                    'limit' => $limit,
                    'imported' => $listCount,
                ];
            case 'MpCustomerOrderNotes':
                $list = CurlExec::getCustomerOrderNotesRecords($endpoint, $token, $limit, $offset);
                $importer = new ImportFromV16($this->module);
                $importer->importMpCustomerOrderNotes($list['remote']);
                $listCount = (int) count($list['remote']);
                $offset += $listCount;

                return [
                    'success' => $list['success'],
                    'done' => $listCount ? false : true,
                    'offset' => $offset,
                    'limit' => $limit,
                    'imported' => $listCount,
                ];
            case 'MpCustomerOrderNotesAttachments':
                $list = CurlExec::getCustomerOrderNotesAttachmentsRecords($endpoint, $token, 500, $offset);
                $importer = new ImportFromV16($this->module);
                $importer->importMpCustomerOrderNotesAttachments($list['remote']);
                $listCount = (int) count($list['remote']);
                $offset += $listCount;

                return [
                    'success' => $list['success'],
                    'done' => $listCount ? false : true,
                    'offset' => $offset,
                    'limit' => $limit,
                    'imported' => $listCount,
                ];
            default:
                return [
                    'success' => 1,
                    'done' => 1,
                    'offset' => -1,
                    'limit' => -1,
                ];
        }
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

    public function ajaxProcessExecCurl()
    {
        $endpoint = \Configuration::get('MPCONNECTOR_ENDPOINT');
        $token = \Configuration::get('MPCONNECTOR_TOKEN');

        $action = \Tools::getValue('action_remote', 'setQuery');
        $query = \Tools::getValue('query', 'select * from ps_orders limit 10');

        return CurlExec::exec($endpoint, $action, $query, $token);
    }
}
