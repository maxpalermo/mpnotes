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

    /**
     * Gestisce l'azione personalizzata dalla griglia degli ordini
     * 
     * @param array $params Parametri della richiesta
     * @return void
     */
    public function ajaxFetchCustomAction($params)
    {
        $id_order = (int) $params['id_order'];

        if (!$id_order) {
            Response::json([
                'success' => false,
                'message' => 'ID ordine mancante',
            ]);
        }

        // Qui puoi implementare la tua logica personalizzata
        // Ad esempio, puoi recuperare informazioni sull'ordine
        $order = new \Order($id_order);

        if (!\Validate::isLoadedObject($order)) {
            Response::json([
                'success' => false,
                'message' => 'Ordine non trovato',
            ]);
        }

        // Esempio di risposta con dati dell'ordine
        Response::json([
            'success' => true,
            'message' => 'Azione personalizzata eseguita con successo',
            'order_info' => [
                'id' => $order->id,
                'reference' => $order->reference,
                'total_paid' => $order->total_paid,
                'date_add' => $order->date_add,
                // Aggiungi altri dati dell'ordine che ti servono
            ],
        ]);
    }

    public function getUploadDir($type)
    {
        switch ($type) {
            case \ModelMpNote::TYPE_NOTE_CUSTOMER:
                return _PS_UPLOAD_DIR_ . 'mpnotes/customer';
            case \ModelMpNote::TYPE_NOTE_ORDER:
                return _PS_UPLOAD_DIR_ . 'mpnotes/order';
            case \ModelMpNote::TYPE_NOTE_EMBROIDERY:
                return _PS_UPLOAD_DIR_ . 'mpnotes/embroidery';
        }

        return '';
    }

    public function ajaxFetchShowNote($params)
    {
        $noteTypeId = (int) $params['type'];
        $noteId = $params['id'] ?? 0;
        $rowId = $params['id_row'] ?? 0;
        $tableName = $params['tableName'] ?? '';
        $isNew = (bool) $params['new'] ?? false;

        $uploadDir = $this->getUploadDir($noteTypeId);

        $result = \ModelMpNote::showNote($noteId, $rowId, $tableName, $noteTypeId, $uploadDir, $isNew);

        Response::json([
            'success' => true,
            'modal' => $result,
            'tableName' => \ModelMpNote::$definition['table'],
        ]);
    }

    public function getNoteTypeId($type)
    {
        switch ($type) {
            case "customer":
                return \ModelMpNote::TYPE_NOTE_CUSTOMER;
            case "order":
                return \ModelMpNote::TYPE_NOTE_ORDER;
            case "embroidery":
                return \ModelMpNote::TYPE_NOTE_EMBROIDERY;
        }

        return 0;
    }

    public function ajaxFetchSaveNote($params)
    {
        $note = $params['note'];
        $noteId = $note['noteId'];
        $fields = [
            'type' => $this->getNoteTypeId($note['noteType']),
            'id_customer' => (int) $note['noteCustomerId'],
            'id_employee' => (int) $this->context->employee->id,
            'id_order' => (int) $note['noteOrderId'],
            'note' => $note['noteText'],
            'alert' => (int) $note['noteAlert'],
            'printable' => (int) $note['notePrintable'],
            'chat' => (int) $note['noteChat'],
            'deleted' => 0,
        ];

        $model = new \ModelMpNote($noteId);
        $model->hydrate($fields);

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
            'tbody' => \ModelMpNote::getListNotesTbody($fields['type'], $fields['id_customer'], $fields['id_order'], $fields['note']),
        ]);
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
        $text = $params['text'];
        $type = (int) $params['type'];
        $id_customer = (int) $params['id_customer'];
        $id_order = (int) $params['id_order'];

        $list = \ModelMpNote::getListNotesTbody($type, $id_customer, $id_order, $text);

        Response::json([
            'success' => true,
            'tbody' => $list,
        ]);
    }
}