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

class MpNotesNotesModuleFrontController extends ModuleFrontController
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
        $id_note = (int) ($this->phpData['id_note'] ?? 0);
        $viewMode = (bool) ($this->phpData['view_mode'] ?? false);

        $html = NoteAttachment::renderNoteAttachmentPanel($id_note, $viewMode);

        return ['success' => true, 'html' => $html['html']];
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
}
