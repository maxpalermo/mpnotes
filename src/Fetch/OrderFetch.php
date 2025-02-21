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

namespace MpSoft\MpNotes\Fetch;

require_once _PS_MODULE_DIR_ . 'mpnotes/src/Models/autoload.php';

use MpSoft\MpNotes\Helpers\Response;

class OrderFetch
{
    private static $instance = null;
    private $module;
    private $context;

    private function __construct()
    {
        $this->module = \Module::getInstanceByName('mpnotes');
        $this->context = \Context::getContext();
    }

    private function __clone()
    {
        // Private clone method to prevent cloning
    }

    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    public function ajaxFetchDoTest($params)
    {
        Response::json([
            'success' => true,
            'message' => 'Test ok',
            'params' => $params,
        ]);
    }

    public function ajaxFetchShowNote($params)
    {
        $tableName = $params['tableName'];
        $id = $params['id_row'];

        switch ($tableName) {
            case 'mp_note_customer':
                $result = $this->newNoteCustomer($params['id_customer'], $id);

                break;
            case 'mp_note_order':
                $uploadDir = $params['noteOrderUploadDir'];
                $result = $this->newNoteOrder($params['id_order'], $id, $uploadDir);

                break;
            case 'mp_note_embroidery':
                $uploadDir = $params['noteEmbroideryUploadDir'];
                $result = $this->newNoteEmbroidery($params['id_customer'], $id, $uploadDir);

                break;
            default:
                return Response::json([
                    'success' => false,
                    'message' => 'Tabella non valida',
                ]);
        }

        Response::json([
            'success' => true,
            'modal' => $result,
            'tableName' => $tableName,
        ]);
    }

    protected function newNoteCustomer($id_customer, $id_row)
    {
        if (!$id_row) {
            $id_customer = 0;
        }

        $tpl = $this->context->smarty->createTemplate($this->module->getLocalPath() . 'views/templates/admin/notes/note_customer.tpl');
        $tpl->assign([
            'link' => $this->context->link,
            'id_customer' => $id_customer,
            'note' => \ModelMpNoteCustomer::getNote($id_customer, $id_row),
        ]);

        return $tpl->fetch();
    }

    protected function newNoteOrder($id_order, $id_row, $uploadDir)
    {
        if (!$id_row) {
            $id_order = 0;
        }

        $tpl = $this->context->smarty->createTemplate($this->module->getLocalPath() . 'views/templates/admin/notes/note_order.tpl');
        $tpl->assign([
            'link' => $this->context->link,
            'id_order' => $id_order,
            'note' => \ModelMpNoteOrder::getNote($id_order, $id_row),
            'uploadDir' => $uploadDir,
        ]);

        return $tpl->fetch();
    }

    protected function newNoteEmbroidery($id_customer, $id_row, $uploadDir)
    {
        if (!$id_row) {
            $id_customer = 0;
        }

        $tpl = $this->context->smarty->createTemplate($this->module->getLocalPath() . 'views/templates/admin/notes/note_embroidery.tpl');
        $tpl->assign([
            'link' => $this->context->link,
            'id_customer' => $id_customer,
            'note' => \ModelMpNoteEmbroidery::getNote($id_customer, $id_row),
            'uploadDir' => $uploadDir,
        ]);

        return $tpl->fetch();
    }

    public function ajaxFetchSaveNoteCustomer($params)
    {
        $note = $params['note'];
        $id_mp_customer_note = (int) $note['noteId'];
        $id_customer = (int) $note['noteCustomerId'];
        $noteText = $note['noteText'];
        $type = (int) $note['noteType'];

        $model = new \ModelMpNoteCustomer($id_mp_customer_note);

        $model->id_customer = $id_customer;
        $model->id_employee = $this->context->employee->id;
        $model->note = $noteText;
        $model->type = $type;

        try {
            if (\Validate::isLoadedObject($model)) {
                $result = $model->update();
            } else {
                $result = $model->add();
            }
        } catch (\Throwable $th) {
            Response::json([
                'success' => false,
                'message' => $th->getMessage(),
            ]);
        }

        Response::json([
            'success' => $result,
            'message' => 'Nota salvata',
            'tbody' => \ModelMpNoteCustomer::getList($id_customer, ''),
        ]);
    }

    public function ajaxFetchSaveNoteOrder($params)
    {
        $note = $params['note'];
        $id_mp_order_note = (int) $note['noteId'];
        $id_order = (int) $note['noteOrderId'];
        $id_customer = (int) $note['noteCustomerId'];
        $noteText = $note['noteText'];
        $type = (int) $note['noteType'];
        $printable = (int) $note['notePrintable'];
        $chat = (int) $note['noteChat'];

        $model = new \ModelMpNoteOrder($id_mp_order_note);

        $model->id_order = $id_order;
        $model->id_customer = $id_customer;
        $model->id_employee = $this->context->employee->id;
        $model->note = $noteText;
        $model->type = $type;
        $model->printable = $printable;
        $model->chat = $chat;

        try {
            if (\Validate::isLoadedObject($model)) {
                $result = $model->update();
            } else {
                $result = $model->add();
            }
        } catch (\Throwable $th) {
            Response::json([
                'success' => false,
                'message' => $th->getMessage(),
            ]);
        }

        Response::json([
            'success' => $result,
            'message' => 'Nota salvata',
            'tbody' => \ModelMpNoteOrder::getList($id_order, ''),
        ]);
    }

    public function ajaxFetchSaveNoteEmbroidery($params)
    {
        $note = $params['note'];
        $id_mp_order_embroidery = (int) $note['noteId'];
        $id_order = (int) $note['noteOrderId'];
        $id_customer = (int) $note['noteCustomerId'];
        $noteText = $note['noteText'];
        $type = (int) $note['noteType'];
        $printable = (int) $note['notePrintable'];

        $model = new \ModelMpNoteEmbroidery($id_mp_order_embroidery);

        $model->id_order = $id_order;
        $model->id_customer = $id_customer;
        $model->id_employee = $this->context->employee->id;
        $model->note = $noteText;
        $model->type = $type;
        $model->printable = $printable;

        try {
            if (\Validate::isLoadedObject($model)) {
                $result = $model->update();
            } else {
                $result = $model->add();
            }
        } catch (\Throwable $th) {
            Response::json([
                'success' => false,
                'message' => $th->getMessage(),
            ]);
        }

        Response::json([
            'success' => $result,
            'message' => 'Nota salvata',
            'tbody' => \ModelMpNoteEmbroidery::getList($id_order, ''),
        ]);
    }

    public function ajaxFetchAddAttachment($params)
    {
        $id_row = (int) $params['id_row'];
        $type = $params['type'];
        $file = $params['file'];
        $tbody = '';

        if ($file['error']) {
            Response::json([
                'success' => false,
                'message' => "File non caricato. Errore: {$file['error']}",
                'id_row' => $id_row,
            ]);
        }

        if ($type == 'order' || $type == 'embroidery') {
            $result = $this->addAttachment($id_row, $type, $file);
            if ($type == 'order') {
                $model = new \ModelMpNoteOrder($id_row);
                $id_order = (int) $model->id_order;
                $tbody = \ModelMpNoteOrder::getTBody($id_order);
            } else {
                $model = new \ModelMpNoteEmbroidery($id_row);
                $id_customer = (int) $model->id_customer;
                $tbody = \ModelMpNoteEmbroidery::getTBody($id_customer);
            }
        } else {
            Response::json([
                'success' => false,
                'message' => "File non caricato. Tipo non valido: {$type}",
                'id_row' => $id_row,
            ]);
        }

        Response::json([
            'success' => $result,
            'message' => 'Allegato inserito in archivio.',
            'tbody' => $tbody,
        ]);
    }

    protected function addAttachment($id_row, $type, $file)
    {
        $source = $file['tmp_name'];
        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = uniqid() . '.' . $extension;
        $dest = _PS_UPLOAD_DIR_ . "mpnotes/{$type}/{$filename}";
        if (!move_uploaded_file($source, $dest)) {
            Response::json([
                'success' => false,
                'message' => 'File non caricato',
                'id_row' => $id_row,
                'type' => $type,
                'file' => $file,
            ]);
        }

        if ($type == 'order') {
            $model = new \ModelMpNoteOrderFile();
        } else {
            $model = new \ModelMpNoteEmbroideryFile();
            $model->id_mp_note_embroidery = $id_row;
            $model->id_employee = $this->context->employee->id;
            $model->path = $filename;
            $model->type = $extension;
            $model->date_add = date('Y-m-d H:i:s');
        }

        try {
            $result = $model->add();
        } catch (\Throwable $th) {
            Response::json([
                'success' => false,
                'message' => $th->getMessage(),
            ]);
        }

        return $result;
    }

    public function ajaxFetchUpdateSearch($params)
    {
        $id = $params['id'];
        $text = $params['text'];
        $type = $params['type'];

        switch ($type) {
            case 'customer':
                $list = \ModelMpNoteCustomer::getList($id, $text);

                break;
            case 'order':
                $list = \ModelMpNoteOrder::getList($id, $text);

                break;
            case 'embroidery':
                $list = \ModelMpNoteEmbroidery::getList($id, $text);

                break;
        }

        Response::json([
            'success' => true,
            'tbody' => $list,
        ]);
    }
}
