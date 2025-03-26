<?php

use MpSoft\MpNotes\Helpers\CreateTemplate;

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
class ModelMpNoteFlag extends ObjectModel
{
    public $name;
    public $color;
    public $icon;
    public $type;
    public $allow_update;
    public $allow_attachments;
    public $active;
    public $date_add;
    public $date_upd;
    public static $definition = [
        'table' => 'mp_note_flag',
        'primary' => 'id_mp_note_flag',
        'fields' => [
            'name' => ['type' => self::TYPE_STRING, 'validate' => 'isGenericName', 'required' => true, 'size' => 32],
            'color' => ['type' => self::TYPE_STRING, 'validate' => 'isColor', 'required' => true, 'size' => 7],
            'icon' => ['type' => self::TYPE_STRING, 'validate' => 'isGenericName', 'required' => true, 'size' => 32],
            'type' => ['type' => self::TYPE_STRING, 'validate' => 'isGenericName', 'required' => true, 'size' => 32],
            'allow_update' => ['type' => self::TYPE_BOOL, 'validate' => 'isBool', 'required' => true, 'default' => 0],
            'allow_attachments' => ['type' => self::TYPE_BOOL, 'validate' => 'isBool', 'required' => true, 'default' => 0],
            'active' => ['type' => self::TYPE_BOOL, 'validate' => 'isBool', 'required' => true, 'default' => 1],
            'date_add' => ['type' => self::TYPE_DATE, 'validate' => 'isDateFormat'],
            'date_upd' => ['type' => self::TYPE_DATE, 'validate' => 'isDateFormat', 'required' => false],
        ],
    ];

    public static function getTableTemplate()
    {
        $db = Db::getInstance();
        $query = new DbQuery();
        $query->select('*')
            ->select(ModelMpNoteFlag::$definition['primary'] . ' as `id`')
            ->from(ModelMpNoteFlag::$definition['table'])
            ->orderBy('name ASC');
        $result = $db->executeS($query);

        if (!$result) {
            $result = [];
        }

        $template = new CreateTemplate('mpnotes');
        $html = $template->createTemplate('tables/tableFlag.tpl', ['rows' => $result]);

        return [
            'success' => true,
            'html' => $html,
        ];
    }

    public static function getMaterialIconsList()
    {
        $list = [
            'flag',
            'note',
            'warning',
            'info',
            'label',
            'bookmark',
            'star',
            'priority_high',
            'error',
            'help',
            'announcement',
            'chat',
            'comment',
            'assignment',
            'attach_file',
            'build',
            'check_circle',
            'description',
            'email',
            'event',
            'favorite',
            'local_shipping',
            'notifications',
            'payment',
            'person',
            'receipt',
            'schedule',
            'shopping_cart',
            'work',
            'account_balance',
            'done',
            'local_offer',
            'euro_symbol',
            'print',
            'settings',
            'anchor',
            'access_time',
            'accessibility',
            'account_box',
            'add_alert',
            'add_shopping_cart',
            'album',
            'assessment',
            'assignment_turned_in',
            'autorenew',
            'backup',
            'book',
            'bug_report',
            'business',
            'cached',
            'calendar_today',
            'call',
            'camera',
            'card_giftcard',
            'cloud',
            'code',
            'contact_mail',
            'credit_card',
            'dashboard',
            'delete',
            'devices',
            'dns',
            'domain',
            'edit',
            'extension',
            'face',
            'feedback',
            'file_copy',
            'folder',
            'gavel',
            'group',
            'headset',
            'home',
            'hourglass_empty',
            'image',
            'language',
            'lightbulb',
            'link',
            'location_on',
            'lock',
            'mail_outline',
            'money',
            'new_releases',
            'palette',
            'people',
            'phone',
            'photo',
            'picture_as_pdf',
            'polymer',
            'timeline',
            'trending_up',
            'trending_down',
            'directions',
            'flight',
            'hotel',
            'restaurant',
            'school',
            'store',
            'watch_later',
            'wifi',
            'bluetooth',
            'call_split',
            'camera_alt',
            'chat_bubble',
            'cloud_download',
            'cloud_upload',
            'content_cut',
            'content_paste',
            'desktop_mac',
            'devices_other',
            'dock',
            'filter_list',
            'folder_open',
            'grade',
            'keyboard',
            'laptop',
            'markunread',
            'memory',
            'navigate_next',
            'public',
            'print',
            'question_answer',
            'receipt',
            'report_problem',
            'room',
            'settings',
            'shopping_cart',
            'smartphone',
            'star',
            'storage',
            'supervisor_account',
            'sync',
            'text_format',
            'theaters',
            'thumb_up',
            'today',
            'track_changes',
            'verified_user',
            'videocam',
            'work',
            'zoom_in',
            'zoom_out',
            'accessibility',
            'account_balance',
            'account_circle',
            'add_alert',
            'announcement',
            'archive',
            'assessment',
            'assignment',
            'attach_file',
            'autorenew',
            'backup',
            'book',
            'bookmark',
            'build',
            'bug_report',
            'category',
            'check_circle',
            'comment',
            'compare',
            'contact_phone',
            'contacts',
            'create',
            'description',
            'developer_mode',
            'done',
            'event',
            'explore',
            'favorite',
            'flag',
            'forum',
            'help',
            'history',
            'info',
            'label',
            'layers',
            'list',
            'local_offer',
            'location_city',
            'loyalty',
            'mail',
            'menu',
            'more',
            'note',
            'notifications',
            'opacity',
            'payment',
            'person',
            'play_circle',
            'power',
            'refresh',
            'save',
            'search',
            'security',
            'send',
            'settings_applications',
            'share',
            'shopping_basket',
            'wifi',
            'spellcheck',
            'subject',
            'subscriptions',
            'support',
            'swap_horiz',
            'tab',
            'timer',
            'translate',
            'update',
            'view_list',
            'visibility',
            'warning',
        ];

        sort($list);

        return array_unique($list);
    }

    public static function getFlags()
    {
        $db = Db::getInstance();
        $query = new DbQuery();
        $query->select('id_mp_note_flag as id, name, icon, color')
            ->from(ModelMpNoteFlag::$definition['table'])
            ->where("type = 'FLAG'")
            ->orderBy('name ASC');
        $result = $db->executeS($query);

        if (!$result) {
            $result = [];
        }

        return $result;
    }

    public static function toggleActive($id)
    {
        $table = _DB_PREFIX_ . self::$definition['table'];
        $primary = self::$definition['primary'];
        $db = Db::getInstance();
        $value = (bool) $db->getValue("SELECT active FROM {$table} WHERE {$primary} = $id");
        $db->update(
            $table,
            ['active' => !$value],
            "{$primary} = $id"
        );
    }
}
