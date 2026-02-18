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
if (!defined('_PS_VERSION_')) {
    exit;
}

use MpSoft\MpNotes\Helpers\JsonDecoder;

class MpNotesConfigModuleFrontController extends ModuleFrontController
{
    protected $phpData;

    public function __construct()
    {
        $this->ajax = 1;
        $this->ssl = Configuration::get('PS_USE_SSL');
        $this->guestAllowed = 1;
        $this->auth = false;

        parent::__construct();

        $jsonPhpData = file_get_contents('php://input');
        if (!$jsonPhpData) {
            $this->phpData = Tools::getAllValues();
        } elseif ($jsonPhpData && !JsonDecoder::isJson($jsonPhpData)) {
            // trasformo l'url query in array
            $split = explode('&', $jsonPhpData);
            $this->phpData = [];
            foreach ($split as $item) {
                $pair = explode('=', $item);
                $this->phpData[$pair[0]] = urldecode($pair[1]);
            }
        } else {
            $this->phpData = JsonDecoder::decodeJson($jsonPhpData, []);
        }
        if ($this->phpData && isset($this->phpData['action']) && $this->phpData['action']) {
            $action = 'processAjax' . Tools::ucfirst($this->phpData['action']);
            if (method_exists($this, $action)) {
                flush();
                header('Content-Type: application/json');

                $result = $this->$action();

                exit(json_encode($result));
            }
        }
    }

    public function processAjaxGetMessageList()
    {
        $type = $this->phpData['type'] ?? null;

        if (!$type) {
            return [
                'success' => false,
                'message' => 'Tipo di operazione non valido',
            ];
        }

        switch ($type) {
            case 'customer':
                $list = $this->getMessageListCustomer();

                break;
            case 'order':
                $list = $this->getMessageListOrder();

                break;
            case 'embroidery':
                $list = $this->getMessageListEmbroidery();

                break;
            default:
                return [
                    'success' => false,
                    'message' => 'Tipo di tabella sconosciuta',
                    'list' => [],
                ];
        }

        // Restituisci l'ID dell'operazione per il polling
        return [
            'success' => true,
            'message' => 'Operazione eseguita',
            'list' => $list,
        ];
    }

    public function getMessageListCustomer()
    {
        $messages = [];
        $db = Db::getInstance();

        $query = new DbQuery();
        $query
            ->select('id_customer')
            ->select('null as id_employee')
            ->select('note as content')
            ->select('id_customer as id_table')
            ->select('date_add')
            ->from('customer')
            ->orderBy('id_customer ASC');
        $result = $db->executeS($query);
        if ($result) {
            $messages = array_merge($messages, $result);
        }

        $query = new DbQuery();
        $query
            ->select('id_customer, id_employee, message as content, date_add')
            ->from('customer_messages')
            ->orderBy('date_add ASC');
        $result = $db->executeS($query);

        if ($result) {
            $messages = array_merge($messages, $result);
        }

        return $messages;
    }

    public function getMessageListOrder()
    {
        $db = Db::getInstance();
        $query = new DbQuery();
        $query
            ->select('id_mp_customer_order_notes as id_table')
            ->select('id_order')
            ->select('id_employee')
            ->select('content')
            ->select('deleted')
            ->select('printable')
            ->select('chat')
            ->select('date_add')
            ->from('mp_customer_order_notes')
            ->orderBy('date_add ASC');
        $result = $db->executeS($query);

        return $result;
    }

    public function getMessageListEmbroidery()
    {
        $db = Db::getInstance();
        $query = new DbQuery();
        $query
            ->select('id_customer_archive as id_table')
            ->select('id_customer')
            ->select('id_order')
            ->select('id_employee')
            ->select('note as content')
            ->select('printable')
            ->select('date_add')
            ->from('customer_archive')
            ->orderBy('date_add ASC');
        $result = $db->executeS($query);

        return $result;
    }

    public function processAjaxTruncateTables()
    {
        $db = Db::getInstance();
        $db->execute('TRUNCATE TABLE ' . _DB_PREFIX_ . ModelMpNote::$definition['table']);
        $db->execute('TRUNCATE TABLE ' . _DB_PREFIX_ . ModelMpNoteAttachment::$definition['table']);

        return [
            'success' => true,
            'message' => 'Tabelle svuotate',
        ];
    }

    public function processAjaxSaveFlagNote()
    {
        $fields = [
            'name' => $this->phpData['name'] ?? null,
            'color' => $this->phpData['color'] ?? null,
            'icon' => $this->phpData['icon'] ?? null,
            'type' => $this->phpData['type'] ?? null,
            'allow_update' => $this->phpData['allow_update'] ?? null,
            'allow_attachments' => $this->phpData['allow_attachments'] ?? null,
            'active' => $this->phpData['active'] ?? 1,
        ];

        $db = Db::getInstance();
        $query = new DbQuery();
        $query
            ->select(ModelMpNoteFlag::$definition['primary'])
            ->from(ModelMpNoteFlag::$definition['table'])
            ->where("name = ':name' AND type = ':type'");

        $sql = $query->build();
        $params = [
            ':name' => pSQL($fields['name']),
            ':type' => pSQL($fields['type']),
        ];

        $sql = $this->prepareQuery($sql, $params);

        $result = $db->getValue($sql);

        if ($result) {
            $model = new ModelMpNoteFlag($result);
        } else {
            $model = new ModelMpNoteFlag();
        }

        $model->hydrate($fields);

        try {
            if (Validate::isLoadedObject($model)) {
                $result = $model->update();
            } else {
                $result = $model->add();
            }
        } catch (\Throwable $th) {
            $result = false;
            $this->errors[] = $th->getMessage();
        }

        return [
            'success' => $result,
            'message' => $result ? 'Flag salvato con successo' : implode('<br>', $this->errors),
        ];
    }

    public function prepareQuery($sql, $params)
    {
        foreach ($params as $key => $value) {
            $sql = str_replace($key, $value, $sql);
        }

        return $sql;
    }

    public function processAjaxUpdateTableNote()
    {
        $template = ModelMpNoteFlag::getTableTemplate();

        return [
            'success' => true,
            'html' => $template['html'] ?? '',
        ];
    }

    public function getNoteTypes()
    {
        return ModelMpNote::getNoteTypes();
    }

    public function processAjaxImportNotes()
    {
        $type = $this->phpData['type'] ?? null;
        $list = $this->phpData['list'] ?? null;
        $id_flag = (int) ($this->phpData['id_flag'] ?? null);
        $flags = ModelMpNoteFlag::getFlags();
        if ($flags) {
            $flags = array_map(function ($flag) {
                return [
                    'id' => $flag['id'],
                    'name' => $flag['name'],
                    'value' => 0,
                ];
            }, $flags);
        }

        if ($list && !is_array($list)) {
            try {
                $list = json_decode($list, true, 512, JSON_THROW_ON_ERROR);
            } catch (\Throwable $th) {
                return [
                    'success' => false,
                    'message' => $th->getMessage(),
                ];
            }
        }

        if (!$type || !$list) {
            return [
                'success' => false,
                'message' => 'Parametri mancanti',
            ];
        }

        switch ($type) {
            case 'customer':
                $result = $this->importCustomerMessages($list, $id_flag);

                break;
            case 'order':
                $result = $this->importOrderMessages($list, $id_flag, $flags);

                break;
            case 'embroidery':
                $result = $this->importEmbroideryMessages($list, $id_flag, $flags);

                break;
            default:
                return [
                    'success' => false,
                    'message' => 'Tipo non valido',
                    'errors' => [],
                ];
        }

        return $result;
    }

    public function importCustomerMessages($list, $id_flag)
    {
        foreach ($list as $item) {
            $item['id_note_type'] = $id_flag;
            $model = new ModelMpNote();
            $model->hydrate($item);

            try {
                $model->add();
            } catch (\Throwable $th) {
                $this->errors[] = sprintf(
                    "Errore durante l'importazione. Cliente id: %d Errore: %s",
                    (int) $item['id_customer'],
                    $th->getMessage()
                );
            }
        }

        return [
            'success' => true,
            'message' => 'Importazione completata con successo',
            'errors' => $this->errors,
        ];
    }

    public function importOrderMessages($list, $id_flag, $flags)
    {
        foreach ($list as $item) {
            $item['id_note_type'] = $id_flag;
            if ($flags) {
                foreach ($flags as &$flag) {
                    $flag['value'] = 0;
                }
                foreach ($flags as &$flag) {
                    if (in_array(strtolower($flag['name']), ['printable', 'stampabile'])) {
                        $flag['value'] = $item['printable'] ?? 0;
                    }

                    if (in_array(strtolower($flag['name']), ['chat'])) {
                        $flag['value'] = $item['chat'] ?? 0;
                    }
                }
                $item['flags'] = json_encode($flags);
            }
            $model = new ModelMpNote();
            $model->hydrate($item);

            try {
                $model->add();
                $id_note = $model->id;
                $this->importAttachment($item['id_table'], $id_note, 'order', 0, $item['id_order']);
            } catch (\Throwable $th) {
                $this->errors[] = sprintf(
                    "Errore durante l'importazione. Cliente id: %d Errore: %s",
                    (int) $item['id_customer'],
                    $th->getMessage()
                );
            }
        }

        return [
            'success' => true,
            'message' => 'Importazione completata con successo',
            'errors' => $this->errors,
        ];
    }

    public function importEmbroideryMessages($list, $id_flag, $flags)
    {
        foreach ($list as $item) {
            $item['id_note_type'] = $id_flag;
            if ($flags) {
                foreach ($flags as &$flag) {
                    $flag['value'] = 0;
                }
                foreach ($flags as &$flag) {
                    if (in_array(strtolower($flag['name']), ['printable', 'stampabile'])) {
                        $flag['value'] = $item['printable'] ?? 0;
                    }

                    if (in_array(strtolower($flag['name']), ['chat'])) {
                        $flag['value'] = $item['chat'] ?? 0;
                    }
                }
                $item['flags'] = json_encode($flags);
            }
            $model = new ModelMpNote();
            $model->hydrate($item);

            try {
                $model->add();
                $id_note = $model->id;
                $this->importAttachment($item['id_table'], $id_note, 'embroidery', $item['id_customer'], $item['id_order']);
            } catch (\Throwable $th) {
                $this->errors[] = sprintf(
                    "Errore durante l'importazione. Cliente id: %d Errore: %s",
                    (int) $item['id_customer'],
                    $th->getMessage()
                );
            }
        }

        return [
            'success' => true,
            'message' => 'Importazione completata con successo',
            'errors' => $this->errors,
        ];
    }

    public function importAttachment($old_id, $new_id, $type, $id_customer = 0, $id_order = 0)
    {
        switch ($type) {
            case 'order':
                $table = 'mp_customer_order_notes_attachments';
                $field = 'id_mp_customer_order_notes';
                $query = new DbQuery();
                $query
                    ->select('filename')
                    ->select('filetitle')
                    ->select('file_ext')
                    ->from($table)
                    ->where($field . ' = ' . (int) $old_id);

                break;
            case 'embroidery':
                $table = 'customer_archive_item';
                $field = 'id_customer_archive';
                $query = new DbQuery();
                $query
                    ->select('path as filename')
                    ->select('path as filetitle')
                    ->select('type as file_ext')
                    ->select('date_add')
                    ->from($table)
                    ->where($field . ' = ' . (int) $old_id);

                break;
            default:
                return false;
        }

        // Carico tutti gli allegati del record
        $db = Db::getInstance();
        $result = $db->executeS($query);
        if ($result) {
            foreach ($result as &$item) {
                $item['id_mpnote'] = $new_id;
                $item['id_customer'] = $id_customer;
                $item['id_order'] = $id_order;
                $item['filename'] = preg_replace('/^\//', '', $item['filename']);

                $model = new ModelMpNoteAttachment();
                $model->hydrate($item);

                try {
                    $model->add();
                } catch (\Throwable $th) {
                    $this->errors[] = sprintf(
                        "Errore durante l'importazione. Nota id: %d Errore: %s",
                        (int) $new_id,
                        $th->getMessage()
                    );
                }
            }
        }
    }

    public function truncateTable($table)
    {
        if (!preg_match('/^' . _DB_PREFIX_ . '/', $table)) {
            $table = _DB_PREFIX_ . $table;
        }
        $db = Db::getInstance();

        return $db->execute("TRUNCATE TABLE `$table`");
    }
}
