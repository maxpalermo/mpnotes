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

namespace MpSoft\MpNotes\Helpers;

use MpSoft\MpNotes\Models\ModelMpNote;
use MpSoft\MpNotes\Models\ModelMpNoteAttachment;
use MpSoft\MpNotes\Models\ModelMpNoteFlag;

class NoteAttachment
{
    /**
     * Render the attachment panel for a note
     *
     * @param int $id_note Note ID
     * @param bool $viewMode If true, the panel will be in view mode (no edit controls)
     *
     * @return array Success status and HTML content
     */
    public static function renderNoteAttachmentPanel($id_note, $viewMode = false)
    {
        $model = new ModelMpNote($id_note);
        if (!\Validate::isLoadedObject($model)) {
            return ['success' => false, 'html' => self::renderAlertEmptyPanel()];
        }

        return ['success' => true, 'html' => self::renderNoteAttachmentPanelContent($model, $viewMode)];
    }

    /**
     * Render an alert for empty panel
     *
     * @return string HTML content
     */
    public static function renderAlertEmptyPanel()
    {
        return '<div class="alert alert-warning">Nessuna nota trovata</div>';
    }

    /**
     * Render the attachment panel content
     *
     * @param ModelMpNote $model Note model
     * @param bool $viewMode If true, the panel will be in view mode (no edit controls)
     *
     * @return string HTML content
     */
    public static function renderNoteAttachmentPanelContent(ModelMpNote $model, $viewMode = false)
    {
        $module = \Module::getInstanceByName('mpnotes');
        $template = new CreateTemplate($module->name);

        // Get attachments for this note
        $attachments = self::getAttachmentsForNote($model->id);

        // Get attachment URL base
        $attachmentUrl = self::getAttachmentUrl();

        // Get AJAX controller URL
        $ajaxController = \Context::getContext()->link->getModuleLink('mpnotes', 'notes');

        return $template->createTemplate('forms/note-attachments.tpl', [
            'note' => $model,
            'attachments' => $attachments,
            'attachment_url' => $attachmentUrl,
            'ajaxController' => $ajaxController,
            'viewMode' => $viewMode,
        ]);
    }

    /**
     * Get attachments for a note
     *
     * @param int $id_note Note ID
     *
     * @return array Attachments
     */
    public static function getAttachmentsForNote($id_note)
    {
        return ModelMpNoteAttachment::getAttachments($id_note, 0);
    }

    /**
     * Get the base URL for attachments
     *
     * @return string Attachment URL
     */
    public static function getAttachmentUrl()
    {
        return _PS_BASE_URL_ . __PS_BASE_URI__ . 'img/mpnotes/';
    }

    /**
     * Get the base URL for attachments
     *
     * @return string Attachment URL
     */
    public static function getAttachmentPath()
    {
        return _PS_IMG_DIR_ . 'mpnotes/';
    }

    /**
     * Upload attachments for a note
     *
     * @param int $id_note Note ID
     * @param array $files Files from $_FILES
     *
     * @return array Success status and message
     */
    public static function uploadAttachments($id_note, $files)
    {
        if (!$id_note) {
            return ['success' => false, 'message' => 'ID nota non valido'];
        }

        if (empty($files) || !isset($files['attachments'])) {
            return ['success' => false, 'message' => 'Nessun file caricato'];
        }

        $note = new ModelMpNote($id_note);
        if (!\Validate::isLoadedObject($note)) {
            return ['success' => false, 'message' => 'Nota non trovata'];
        }

        if (!$note->allowAttachments()) {
            return ['success' => false, 'message' => 'Gli allegati non sono consentiti per questa nota'];
        }

        // Create upload directory if it doesn't exist
        $uploadDir = self::getAttachmentPath();
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $uploadedFiles = [];
        $errors = [];

        // Process each file
        $fileCount = count($files['attachments']['name']);
        for ($i = 0; $i < $fileCount; $i++) {
            $fileTitle = uniqid();
            $fileName = $files['attachments']['name'][$i];
            $fileType = $files['attachments']['type'][$i];
            $fileTmpName = $files['attachments']['tmp_name'][$i];
            $fileError = $files['attachments']['error'][$i];
            $fileSize = $files['attachments']['size'][$i];

            // Check for errors
            if ($fileError !== UPLOAD_ERR_OK) {
                $errors[] = "Errore durante il caricamento di {$fileName}: " . self::getUploadErrorMessage($fileError);

                continue;
            }

            // Check file type
            $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp', 'application/pdf'];
            if (!in_array($fileType, $allowedTypes)) {
                $errors[] = "Tipo di file non supportato per {$fileName}. Sono consentiti solo immagini e PDF.";

                continue;
            }

            // Generate unique filename
            $fileExt = pathinfo($fileName, PATHINFO_EXTENSION);
            $newFileName = $fileTitle . '.' . $fileExt;
            $targetFilePath = $uploadDir . $newFileName;

            // Move uploaded file
            if (move_uploaded_file($fileTmpName, $targetFilePath)) {
                // Create attachment record
                $attachment = new ModelMpNoteAttachment();
                $attachment->id_mpnote = $id_note;
                $attachment->id_customer = $note->id_customer;
                $attachment->id_order = $note->id_order;
                $attachment->id_mpnote_flag = 0;  // Default flag
                $attachment->filename = $newFileName;
                $attachment->filetitle = $fileName;
                $attachment->file_ext = strtolower($fileExt);

                if ($attachment->add()) {
                    $uploadedFiles[] = $attachment;
                } else {
                    $errors[] = "Errore durante il salvataggio del record per {$fileName}";
                    // Remove file if record creation failed
                    if (file_exists($targetFilePath)) {
                        unlink($targetFilePath);
                    }
                }
            } else {
                $errors[] = "Errore durante lo spostamento del file {$fileName}";
            }
        }

        if (!empty($errors)) {
            return ['success' => false, 'message' => implode("\n", $errors)];
        }

        return [
            'success' => true,
            'message' => count($uploadedFiles) . ' allegati caricati con successo',
            'files' => $uploadedFiles,
        ];
    }

    /**
     * Delete an attachment
     *
     * @param int $id_attachment Attachment ID
     *
     * @return array Success status and message
     */
    public static function deleteAttachment($id_attachment)
    {
        if (!$id_attachment) {
            return ['success' => false, 'message' => 'ID allegato non valido'];
        }

        $attachment = new ModelMpNoteAttachment($id_attachment);
        if (!\Validate::isLoadedObject($attachment)) {
            return ['success' => false, 'message' => 'Allegato non trovato'];
        }

        // Check if user has permission to delete this attachment
        $note = new ModelMpNote($attachment->id_mpnote);
        if (!\Validate::isLoadedObject($note) || !$note->allowUpdate()) {
            return ['success' => false, 'message' => 'Non hai i permessi per eliminare questo allegato'];
        }

        // Delete file
        $filePath = self::getAttachmentPath() . $attachment->filename;
        if (file_exists($filePath)) {
            unlink($filePath);
        }

        // Delete record
        if ($attachment->delete()) {
            return ['success' => true, 'message' => 'Allegato eliminato con successo'];
        }

        return ['success' => false, 'message' => "Errore durante l'eliminazione dell'allegato"];
    }

    /**
     * Get upload error message
     *
     * @param int $errorCode Upload error code
     *
     * @return string Error message
     */
    private static function getUploadErrorMessage($errorCode)
    {
        switch ($errorCode) {
            case UPLOAD_ERR_INI_SIZE:
                return 'Il file caricato supera la direttiva upload_max_filesize in php.ini';
            case UPLOAD_ERR_FORM_SIZE:
                return 'Il file caricato supera la direttiva MAX_FILE_SIZE specificata nel form HTML';
            case UPLOAD_ERR_PARTIAL:
                return 'Il file è stato caricato solo parzialmente';
            case UPLOAD_ERR_NO_FILE:
                return 'Nessun file è stato caricato';
            case UPLOAD_ERR_NO_TMP_DIR:
                return 'Manca una cartella temporanea';
            case UPLOAD_ERR_CANT_WRITE:
                return 'Impossibile scrivere il file su disco';
            case UPLOAD_ERR_EXTENSION:
                return "Caricamento del file interrotto da un'estensione PHP";
            default:
                return 'Errore di caricamento sconosciuto';
        }
    }
}
