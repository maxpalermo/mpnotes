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

class NoteManager
{
    protected $module;
    protected $context;
    protected $id_lang;
    protected $gravityIcons = [
        'info' => 'info',
        'warning' => 'warning',
        'error' => 'error',
        'success' => 'check_circle',
    ];

    public function __construct()
    {
        $this->module = \Module::getInstanceByName('mpnotes');
        $this->context = \Context::getContext();
        $this->id_lang = (int) $this->context->language->id;
    }

    public function render($id_order = 0, $id_customer = 0, $showsWhere = [])
    {
        $template = new CreateTemplate($this->module->name);
        $summary = $template->createTemplate('panels/panelSummaryNotes.tpl', [
            'summaryContent' => $this->renderNotes($id_order, $id_customer, $showsWhere),
            'ajaxController' => $this->context->link->getModuleLink($this->module->name, 'Notes'),
            'id_employee' => (int) $this->context->employee->id,
        ]);

        return $summary;
    }

    public function renderNotes($id_order = 0, $id_customer = 0, $showsWhere = [])
    {
        $noteTypes = $this->getNoteTypes($showsWhere);
        $out = [];
        foreach ($noteTypes as $type) {
            $html = $this->renderNote($type['id'], $id_order, $id_customer);
            if ($html) {
                $out[$type['id']] = $html;
            }
        }

        return implode('', $out);
    }

    public function renderNote($id, $id_order = 0, $id_customer = 0)
    {
        $model = new \ModelMpNoteFlag($id);
        if (!\Validate::isLoadedObject($model)) {
            return '';
        }

        $panel = (new CreateTemplate($this->module->name))
            ->createTemplate('panels/panelNoteMain.tpl', [
                'id' => $id,
                'toolbar' => $this->renderNoteToolbar($id, $id_order, $id_customer),
                'content' => $this->renderContent($id, $id_order, $id_customer),
            ]);

        return $panel;
    }

    public function renderNoteToolbar($id, $id_order = 0, $id_customer = 0)
    {
        $model = new \ModelMpNoteFlag($id);
        if (!\Validate::isLoadedObject($model)) {
            return '';
        }

        $id_employee = (int) $this->context->employee->id;
        $template = new CreateTemplate($this->module->name);
        $html = $template->createTemplate('panels/panelNoteToolbar.tpl', [
            'id' => $id,
            'icon' => $model->icon,
            'title' => $model->name,
            'color' => $model->color,
            'id_order' => $id_order,
            'id_customer' => $id_customer,
            'id_employee' => $id_employee,
            'noteCount' => $this->getNoteCount($model->id, $id_order, $id_customer, $model->always_show),
        ]);

        return $html;
    }

    public function renderContent($id_type, $id_order = 0, $id_customer = 0)
    {
        if (!$id_order && !$id_customer) {
            return [];
        }

        $notes = $this->getNotes($id_type, $id_order, $id_customer);

        $template = new CreateTemplate($this->module->name);
        $html = $template->createTemplate('panels/panelNoteContent.tpl', [
            'id_lang' => $this->id_lang,
            'id_order' => $id_order,
            'id_customer' => $id_customer,
            'id_type' => $id_type,
            'notes' => $notes,
        ]);

        return $html;
    }

    public function getNoteTypes($showsWhere = [])
    {
        $db = \Db::getInstance();
        $query = new \DbQuery();
        $query->select('*, id_mpnote_flag as id')
            ->from('mpnote_flag')
            ->where("type = 'NOTE'")
            ->orderBy('id_mpnote_flag ASC');

        if (!empty($showsWhere)) {
            foreach ($showsWhere as $key => $value) {
                $query->where($key . ' = ' . (int) $value);
            }
        }

        return $db->executeS($query);
    }

    public function getNoteCount($id_type = 0, $id_order = 0, $id_customer = 0, $always = false)
    {
        $query = new \DbQuery();
        $query->select('COUNT(*)')
            ->from('mpnote')
            ->where("id_note_type = {$id_type}");

        if ($always && $id_customer) {
            if ($id_order) {
                $query->where("id_customer = {$id_customer} OR id_order = {$id_order}");
            } else {
                $query->where("id_customer = {$id_customer}");
            }
        } else {
            if ($id_customer && $id_order) {
                $query->where("id_customer = {$id_customer} OR id_order = {$id_order}");
            } else {
                if ($id_customer) {
                    $query->where("id_customer = {$id_customer}");
                }
                if ($id_order) {
                    $query->where("id_order = {$id_order}");
                }
            }
        }

        $sql = $query->build();

        return (int) \Db::getInstance()->getValue($sql);
    }

    public function getNotes($id_type = 0, $id_order = 0, $id_customer = 0, $always = false)
    {
        $query = new \DbQuery();
        $query->select('id_mpnote')
            ->from('mpnote')
            ->where("id_note_type = {$id_type}")
            ->orderBy('date_add DESC');

        if ($always && $id_customer) {
            if ($id_order) {
                $query->where("id_customer = {$id_customer} OR id_order = {$id_order}");
            } else {
                $query->where("id_customer = {$id_customer}");
            }
        } else {
            if ($id_customer && $id_order) {
                $query->where("id_customer = {$id_customer} OR id_order = {$id_order}");
            } else {
                if ($id_customer) {
                    $query->where("id_customer = {$id_customer}");
                }
                if ($id_order) {
                    $query->where("id_order = {$id_order}");
                }
            }
        }

        $sql = $query->build();

        $rows = \Db::getInstance()->executeS($sql);
        if ($rows) {
            foreach ($rows as &$row) {
                $model = new \ModelMpNote($row['id_mpnote']);
                if (!\Validate::isLoadedObject($model)) {
                    continue;
                }
                $fields = $model->getFieldsList();
                if (!isset($fields['id'])) {
                    $fields['id'] = $fields['id_mpnote'];
                }
                if (!isset($fields['flags'])) {
                    $fields['flags'] = $this->getFlags($fields['flags']);
                }

                $fields['gravity_icon'] = $this->gravityIcons[$fields['gravity']] ?? 'help';
                $fields['attachments'] = $this->getAttachments($fields['id'], $id_type);

                $row = $fields;
            }
        } else {
            $rows = [];
        }

        return $rows;
    }

    public function getNotesOld($id_type = 0, $id_order = 0, $id_customer = 0, $always = false)
    {
        $query = new \DbQuery();
        $query->select('*, id_mpnote as id')
            ->from('mpnote')
            ->where("id_note_type = {$id_type}")
            ->orderBy('date_add DESC');

        if ($always && $id_customer) {
            if ($id_order) {
                $query->where("id_customer = {$id_customer} OR id_order = {$id_order}");
            } else {
                $query->where("id_customer = {$id_customer}");
            }
        } else {
            if ($id_customer && $id_order) {
                $query->where("id_customer = {$id_customer} OR id_order = {$id_order}");
            } else {
                if ($id_customer) {
                    $query->where("id_customer = {$id_customer}");
                }
                if ($id_order) {
                    $query->where("id_order = {$id_order}");
                }
            }
        }

        $sql = $query->build();

        $rows = \Db::getInstance()->executeS($sql);
        if ($rows) {
            foreach ($rows as &$row) {
                $row['flags'] = $this->getFlags($row['flags']);
                $row['gravity_icon'] = $this->gravityIcons[$row['gravity']] ?? 'help';
                $row['attachments'] = $this->getAttachments($row['id'], $id_type);
            }
        } else {
            $rows = [];
        }

        return $rows;
    }

    public function getFlags($flags)
    {
        if (JsonDecoder::isJson($flags)) {
            $flags = JsonDecoder::decodeJson($flags, []);
        }

        if ($flags && is_array($flags)) {
            foreach ($flags as &$flag) {
                $objFlag = new \ModelMpNoteFlag($flag['id']);
                if (!\Validate::isLoadedObject($objFlag)) {
                    $flag['color'] = '#c3c3c3';
                    $flag['icon'] = 'help';
                    $flag['allow_update'] = false;
                    $flag['allow_attachments'] = false;
                } else {
                    $flag['color'] = $objFlag->color;
                    $flag['icon'] = $objFlag->icon;
                    $flag['allow_update'] = $objFlag->allow_update;
                    $flag['allow_attachments'] = $objFlag->allow_attachments;
                }
            }
        }

        return $flags;
    }

    public function getAttachments($id_note, $id_type)
    {
        $flag = new \ModelMpNoteFlag($id_type);
        if (!\Validate::isLoadedObject($flag)) {
            return false;
        }

        if ($flag->allow_attachments == false) {
            return false;
        }

        $query = new \DbQuery();
        $query->select('count(' . \ModelMpNoteAttachment::$definition['primary'] . ')')
            ->from(\ModelMpNoteAttachment::$definition['table'])
            ->where('id_mpnote = ' . (int) $id_note);

        return (int) \Db::getInstance()->getValue($query);
    }

    public function getTestNoteContent($id)
    {
        return "
        <div class='card-body overflow-y-scroll collapse' id='panelNoteCollapse-{$id}'>
            <div class='alert alert-info'>This is a test note</div>
        </div>
        ";
    }
}
