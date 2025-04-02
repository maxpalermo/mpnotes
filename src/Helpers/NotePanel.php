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

class NotePanel
{
    public static function renderNotePanel($id_row, $id_note_type, $id_order, $id_customer, $id_employee)
    {
        if ($id_row) {
            $model = new \ModelMpNote($id_row);
            if (!\Validate::isLoadedObject($model)) {
                return ['success' => false, 'html' => self::renderAlertEmptyPanel()];
            }
        } else {
            $model = new \ModelMpNote();
            $model->setNoteType($id_note_type);
            $model->setOrderId($id_order);
            $model->setCustomerId($id_customer);
            $model->setEmployeeId($id_employee);
            $model->setFlags();
        }

        return ['success' => true, 'html' => self::renderNotePanelContent($model)];
    }

    public static function renderAlertEmptyPanel()
    {
        return '<div class="alert alert-warning">Nessuna nota trovata</div>';
    }

    public static function renderNotePanelContent(\ModelMpNote $model)
    {
        $module = \Module::getInstanceByName('mpnotes');
        $template = new CreateTemplate($module->name);
        $html = $template->createTemplate('forms/note-panel.tpl', ['note' => $model]);

        return $html;
    }
}
