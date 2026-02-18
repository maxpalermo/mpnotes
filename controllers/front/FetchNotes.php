<?php

use MpSoft\MpNotes\Helpers\FetchHandler;

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

use MpSoft\MpNotes\Helpers\NoteAttachment;
use MpSoft\MpNotes\Helpers\NotePanel;
use MpSoft\MpNotes\Models\ModelMpNote;
use MpSoft\MpNotes\Models\ModelMpNoteAttachment;
use MpSoft\MpNotes\Models\ModelMpNoteFlag;

class MpNotesFetchNotesModuleFrontController extends ModuleFrontController
{
    protected $phpData;

    public function __construct()
    {
        $this->ajax = 1;
        $this->ssl = Configuration::get('PS_USE_SSL');
        $this->guestAllowed = 1;
        $this->auth = false;

        parent::__construct();

        $fetchHandler = new FetchHandler($this->module, $this);
        $this->phpData = $fetchHandler->getPhpData();
        $fetchHandler->run();
    }

    public function display()
    {
        exit(json_encode(['success' => false, 'message' => 'Metodo non trovato']));
    }

    /**
     * Get note panel HTML
     *
     * @return array Response with HTML content
     */
    public function getNote()
    {
        $id_row = (int) ($this->phpData['id_row'] ?? 0);
        $id_note_type = (int) ($this->phpData['id_note_type'] ?? 0);
        $id_order = (int) ($this->phpData['id_order'] ?? 0);
        $id_customer = (int) ($this->phpData['id_customer'] ?? 0);
        $id_employee = (int) ($this->phpData['id_employee'] ?? 0);

        $html = NotePanel::renderNotePanel($id_row, $id_note_type, $id_order, $id_customer, $id_employee);
        if ($html['success']) {
            return ['success' => true, 'html' => $html['html']];
        }

        return ['success' => false, 'message' => NotePanel::renderAlertEmptyPanel()];
    }

    public function createNote()
    {
        $id_note = 0;
        $id_note_type = (int) ($this->phpData['noteTypeId'] ?? 0);
        $id_order = (int) ($this->phpData['orderId'] ?? 0);
        $id_customer = (int) ($this->phpData['customerId'] ?? 0);
        $id_employee = (int) ($this->phpData['employeeId'] ?? 0);
        $content = (string) ($this->phpData['content'] ?? '');
        $gravity = (string) ($this->phpData['gravity'] ?? 'info');
        $flags = [];
        foreach ($this->phpData as $key => $value) {
            if (str_starts_with($key, 'flag-note-')) {
                $flagId = (int) substr($key, strlen('flag-note-'));
                $flagValue = (int) ($this->phpData['flag-note-' . $flagId] ?? 0);
                $flags[$flagId] = $flagValue;
            }
        }

        $note = new ModelMpNote($id_note);
        $note->id_order = $id_order;
        $note->id_customer = $id_customer;
        $note->id_employee = $id_employee;
        $note->content = $content;
        $note->gravity = $gravity;
        $note->id_note_type = $id_note_type;
        foreach ($note->flags as &$flag) {
            if (isset($flags[$flag['id']])) {
                $flag['value'] = (int) $flags[$flag['id']];
            }
        }

        try {
            $note->add();
        } catch (\Throwable $th) {
            return ['success' => false, 'message' => $th->getMessage()];
        }

        return ['success' => true, 'id_note' => $note->id];
    }

    public function updateNote()
    {
        $id_note = (int) ($this->phpData['noteId'] ?? 0);
        $id_note_type = (int) ($this->phpData['noteTypeId'] ?? 0);
        $id_order = (int) ($this->phpData['orderId'] ?? 0);
        $id_customer = (int) ($this->phpData['customerId'] ?? 0);
        $id_employee = (int) ($this->phpData['employeeId'] ?? 0);
        $content = (string) ($this->phpData['content'] ?? '');
        $gravity = (string) ($this->phpData['gravity'] ?? 'info');
        $flags = [];

        if (!$id_note) {
            return ['success' => false, 'message' => 'ID nota non specificato'];
        }

        foreach ($this->phpData as $key => $value) {
            if (str_starts_with($key, 'flag-note-')) {
                $flagId = (int) substr($key, strlen('flag-note-'));
                $flagValue = (int) ($this->phpData['flag-note-' . $flagId] ?? 0);
                $flags[$flagId] = $flagValue;
            }
        }

        $note = new ModelMpNote($id_note);
        if (!\Validate::isLoadedObject($note)) {
            return ['success' => false, 'message' => 'Nota non trovata'];
        }

        $note->id_order = $id_order;
        $note->id_customer = $id_customer;
        $note->id_employee = $id_employee;
        $note->content = $content;
        $note->gravity = $gravity;
        $note->id_note_type = $id_note_type;
        foreach ($note->flags as &$flag) {
            if (isset($flags[$flag['id']])) {
                $flag['value'] = (int) $flags[$flag['id']];
            }
        }

        try {
            $note->update();
        } catch (\Throwable $th) {
            return ['success' => false, 'message' => $th->getMessage()];
        }

        return ['success' => true, 'id_note' => $note->id];
    }

    /**
     * Get attachments panel HTML
     *
     * @return array Response with HTML content
     */
    public function getAttachments()
    {
        $data = json_decode(Tools::getValue('data'), true);
        $idNote = (int) $data['idNote'] ?? 0;
        $typeNote = $data['typeNote'] ?? '';

        switch ($typeNote) {
            case 'customer':
                $typeNote = ModelMpNote::TYPE_CUSTOMER;
                break;
            case 'order':
                $typeNote = ModelMpNote::TYPE_ORDER;
                break;
            case 'embroidery':
                $typeNote = ModelMpNote::TYPE_EMBROIDERY;
                break;
            default:
                $typeNote = ModelMpNote::TYPE_CUSTOMER;
                break;
        }

        $attachments = ModelMpNoteAttachment::getAttachments($idNote, $typeNote);

        return ['success' => true, 'data' => $attachments];
    }

    /**
     * Upload attachments for a note
     *
     * @return array Response with success status and message
     */
    public function uploadAttachments()
    {
        $id_note = (int) ($this->phpData['id_note'] ?? 0);

        if (!$id_note) {
            return ['success' => false, 'message' => 'ID nota non specificato'];
        }

        // Check if files were uploaded
        if (empty($_FILES)) {
            return ['success' => false, 'message' => 'Nessun file caricato'];
        }

        // Process file uploads
        $result = NoteAttachment::uploadAttachments($id_note, $_FILES);

        return $result;
    }

    public function addAttachment()
    {
        $file = Tools::fileAttachment('MpNoteAttachment', false);
        $idNote = (int) Tools::getValue('idNote');
        $idOrder = (int) Tools::getValue('idOrder');
        $idCustomer = (int) Tools::getValue('idCustomer');
        $idType = Tools::getValue('idType');

        switch ($idType) {
            case 'customer':
                $idType = ModelMpNote::TYPE_CUSTOMER;
                break;
            case 'order':
                $idType = ModelMpNote::TYPE_ORDER;
                break;
            case 'embroidery':
                $idType = ModelMpNote::TYPE_EMBROIDERY;
                break;
            default:
                return ['success' => false, 'message' => 'Tipo di nota non valido'];
        }
        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = $this->createUUID() . '.' . $extension;
        $dest = _PS_IMG_DIR_ . 'mpnotes/' . $filename;
        if (!file_exists(_PS_IMG_DIR_ . 'mpnotes')) {
            mkdir(_PS_IMG_DIR_ . 'mpnotes', 0777, true);
        }

        if (!move_uploaded_file($file['tmp_name'], $dest)) {
            return ['success' => false, 'message' => 'Impossibile spostare il file'];
        }

        $attachment = new ModelMpNoteAttachment();
        $attachment->type_note = $idType;
        $attachment->id_mpnote = $idNote;
        $attachment->id_customer = $idCustomer;
        $attachment->id_order = $idOrder;
        $attachment->filename = $filename;
        $attachment->file_ext = $extension;
        $attachment->filetitle = $file['name'];
        $attachment->deleted = false;
        try {
            $attachment->add();
        } catch (\Throwable $th) {
            return ['success' => false, 'message' => $th->getMessage()];
        }

        return [
            'success' => true,
            'data' => [
                'idAttachment' => $attachment->id,
                'idNote' => $idNote,
                'idOrder' => $idOrder,
                'idCustomer' => $idCustomer,
                'idType' => $idType,
                'file' => $file
            ]
        ];
    }

    protected function createUUID()
    {
        $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        $uuid = '';
        for ($i = 0; $i < 16; $i++) {
            $uuid .= $chars[rand(0, strlen($chars) - 1)];
        }
        return $uuid;
    }

    /**
     * Delete an attachment
     *
     * @return array Response with success status and message
     */
    public function deleteAttachment()
    {
        $id_attachment = (int) ($this->phpData['id_attachment'] ?? 0);

        if (!$id_attachment) {
            return ['success' => false, 'message' => 'ID allegato non specificato'];
        }

        // Delete attachment
        $result = NoteAttachment::deleteAttachment($id_attachment);

        return $result;
    }

    public function submitNote()
    {
        $values = json_decode(Tools::getValue('data'), true);
        if (!$values) {
            return ['success' => false, 'message' => 'Dati non validi'];
        }

        /*
         * "type": "customer",
         * "id": "0",
         * "MpNoteOrderId": "138225",
         * "MpNoteCustomerId": "21244",
         * "MpNoteCustomerName": "bingo ritz",
         * "MpNoteEmployeeId": "1",
         * "MpNoteEmployee": "Massimiliano Palermo",
         * "MpNoteContent": "ciao",
         * "MpNotePrintable": "1",
         * "MpNoteChat": "1"
         */

        $type = (string) ($values['type'] ?? '');
        $id = (int) ($values['id'] ?? 0);
        $orderId = (int) ($values['MpNoteOrderId'] ?? 0);
        $customerId = (int) ($values['MpNoteCustomerId'] ?? 0);
        $customerName = (string) ($values['MpNoteCustomerName'] ?? '');
        $employeeId = (int) ($values['MpNoteEmployeeId'] ?? 0);
        $employeeName = (string) ($values['MpNoteEmployee'] ?? '');
        $content = (string) ($values['MpNoteContent'] ?? '');
        $printable = (int) ($values['MpNotePrintable'] ?? 0);
        $chat = (int) ($values['MpNoteChat'] ?? 0);
        $gravity = 'info';

        switch ($type) {
            case 'customer':
                $id_note_type = ModelMpNote::TYPE_CUSTOMER;
                break;
            case 'order':
                $id_note_type = ModelMpNote::TYPE_ORDER;
                break;
            default:
                $id_note_type = ModelMpNote::TYPE_EMBROIDERY;
                break;
        }

        $flags = [
            'printable' => $printable,
            'chat' => $chat,
        ];

        $note = new ModelMpNote($id);
        $note->id_note_type = $id_note_type;
        $note->id_customer = $customerId;
        $note->id_employee = $employeeId;
        $note->id_order = $orderId;
        $note->content = $content;
        $note->gravity = $gravity;
        $note->content = $content;
        $note->flags = $flags;
        $note->deleted = 0;

        try {
            if ($id) {
                $note->update();
            } else {
                $note->add();
            }
        } catch (\Throwable $th) {
            return ['success' => false, 'message' => $th->getMessage()];
        }

        $values['success'] = true;
        $values['message'] = 'Nota creata con successo';
        return $values;
    }

    public function getNoteById()
    {
        $data = json_decode(Tools::getValue('data'), true);
        $id_note = (int) $data['idNote'];
        $id_order = (int) $data['idOrder'];
        $id_employee = (int) $data['idEmployee'];

        if (!$id_note) {
            return ['success' => false, 'message' => 'ID nota non specificato'];
        }

        if (!$id_order) {
            return ['success' => false, 'message' => 'ID ordine non specificato'];
        }

        $order = new Order($id_order);
        if (!\Validate::isLoadedObject($order)) {
            return ['success' => false, 'message' => 'Ordine non trovato'];
        }
        $customer = new Customer($order->id_customer);
        if (!\Validate::isLoadedObject($customer)) {
            return ['success' => false, 'message' => 'Cliente non trovato'];
        }

        $employee = new Employee($id_employee);
        if (!\Validate::isLoadedObject($employee)) {
            return ['success' => false, 'message' => 'Operatore non trovato'];
        }
        $employeeName = $employee->firstname . ' ' . $employee->lastname;
        $customerName = $customer->firstname . ' ' . $customer->lastname;

        $note = new ModelMpNote($id_note);
        if (!\Validate::isLoadedObject($note)) {
            return ['success' => false, 'message' => 'Nota non trovata'];
        }

        $result['id'] = $note->id;
        $result['employeeId'] = $note->id_employee;
        $result['employee_name'] = strtoupper($employeeName);
        $result['customer_id'] = $note->id_customer;
        $result['customer_name'] = strtoupper($customerName);
        $result['id_order'] = $note->id_order;
        $result['content'] = $note->content;
        $result['gravity'] = $note->gravity;
        $result['flags'] = $note->flags;
        $result['deleted'] = $note->deleted;
        $result['id_note_type'] = $note->id_note_type;

        return ['success' => true, 'note' => $result];
    }
}
