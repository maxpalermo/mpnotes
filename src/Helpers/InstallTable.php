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

class InstallTable
{
    const INDEX_TYPE_PRIMARY = 'PRIMARY';
    const INDEX_TYPE_UNIQUE = 'UNIQUE';
    const INDEX_TYPE_INDEX = 'INDEX';
    const INDEX_TYPE_FULLTEXT = 'FULLTEXT';
    const INDEX_TYPE_FOREIGN = 'FOREIGN';
    const CASCADE = 'CASCADE';
    const SET_NULL = 'SET NULL';
    const RESTRICT = 'RESTRICT';
    const NO_ACTION = 'NO ACTION';
    const MATCH = 'MATCH';

    public static function install($definition)
    {
        $pfx = _DB_PREFIX_;
        $table = $definition['table'];
        $primary = $definition['primary'];
        $fields = [];
        $fields_lang = [];
        $is_multilang = isset($definition['multilang']) && $definition['multilang'];
        foreach ($definition['fields'] as $field_name => $field_definition) {
            if (isset($field_definition['lang']) && $field_definition['lang']) {
                $fields_lang[] = $field_name;
            } else {
                $fields[] = $field_name;
            }
        }
        $sql = "CREATE TABLE IF NOT EXISTS `{$pfx}{$table}` ("
            . "`{$primary}` INT NOT NULL AUTO_INCREMENT, "
            . implode(', ', array_map(function ($f) use ($definition) {
                return self::getFieldDefinition($definition, $f);
            }, $fields))
            . ", PRIMARY KEY (`{$primary}`)"
            . ') ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8';
        \Db::getInstance()->execute($sql);
        if ($is_multilang) {
            $sql = "CREATE TABLE IF NOT EXISTS `{$pfx}{$table}_lang` ("
                . "`{$primary}` INT NOT NULL, "
                . '`id_lang` INT NOT NULL, '
                . implode(', ', array_map(function ($f) use ($definition) {
                    return self::getFieldDefinition($definition, $f, true);
                }, $fields_lang))
                . ", PRIMARY KEY (`{$primary}`, `id_lang`)"
                . ') ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8';
            \Db::getInstance()->execute($sql);
        }

        return true;
    }

    private static function getFieldDefinition($definition, $field_name, $is_lang = false)
    {
        $field_definition = $definition['fields'][$field_name];
        $sql_field = "`{$field_name}` ";
        if (isset($field_definition['type']) && $field_definition['type'] === 'datetime') {
            $sql_field .= 'DATETIME';
        } elseif (isset($field_definition['type']) && $field_definition['type'] === 'price') {
            $sql_field .= 'DECIMAL(20,6)';
        } elseif (isset($field_definition['type']) && $field_definition['type'] === 'enum') {
            $sql_field .= 'ENUM(\'' . implode('\',\'', $field_definition['values']) . '\')';
        } elseif (isset($field_definition['type']) && $field_definition['type'] === 'string') {
            if (isset($field_definition['size']) && $field_definition['size'] > 0) {
                $sql_field .= "VARCHAR({$field_definition['size']})";
            } else {
                $sql_field .= 'TEXT';
            }
        } elseif (isset($field_definition['type']) && $field_definition['type'] === 'fixed') {
            $sql_field .= "CHAR({$field_definition['size']})";
        } else {
            $sql_field .= 'VARCHAR(255)';
        }
        if (isset($field_definition['required']) && $field_definition['required']) {
            $sql_field .= ' NOT NULL';
        }
        if (isset($field_definition['default_value'])) {
            $sql_field .= " DEFAULT '{$field_definition['default_value']}'";
        }

        return $sql_field;
    }

    public static function addIndex($table, $fields, $name = '', $type = self::INDEX_TYPE_INDEX)
    {
        $pfx = _DB_PREFIX_;
        if (is_string($fields)) {
            $fields = [$fields];
        }
        $sql = "CREATE {$type} INDEX ";
        if ($name) {
            $sql .= "`{$name}` ";
        }
        $sql .= "ON `{$pfx}{$table}` (";
        $sql .= implode(', ', array_map(function ($f) {
            return "`{$f}`";
        }, $fields));
        $sql .= ')';

        \Db::getInstance()->execute($sql);

        return true;
    }

    public static function dropIndex($table, $name)
    {
        $pfx = _DB_PREFIX_;
        $sql = "DROP INDEX `{$name}` ON `{$pfx}{$table}`";

        \Db::getInstance()->execute($sql);

        return true;
    }

    public static function dropTable($table)
    {
        $pfx = _DB_PREFIX_;
        $sql = "DROP TABLE IF EXISTS `{$pfx}{$table}`";

        \Db::getInstance()->execute($sql);

        return true;
    }

    public static function truncateTable($table)
    {
        $pfx = _DB_PREFIX_;
        $sql = "TRUNCATE TABLE `{$pfx}{$table}`";

        \Db::getInstance()->execute($sql);

        return true;
    }

    public static function addForeignKey($table, $fields, $name = '', $onTable, $onFields, $onUpdate = self::CASCADE, $onDelete = self::CASCADE)
    {
        $pfx = _DB_PREFIX_;
        if (is_string($fields)) {
            $fields = [$fields];
        }
        $sql = "ALTER TABLE `{$pfx}{$table}` ADD CONSTRAINT ";
        if ($name) {
            $sql .= "`{$name}` ";
        }
        $sql .= 'FOREIGN KEY (';
        $sql .= implode(', ', array_map(function ($f) {
            return "`{$f}`";
        }, $fields));
        $sql .= ") REFERENCES `{$pfx}{$onTable}` (";
        $sql .= implode(', ', array_map(function ($f) {
            return "`{$f}`";
        }, $onFields));
        $sql .= ") ON DELETE {$onDelete} ON UPDATE {$onUpdate}";

        \Db::getInstance()->execute($sql);

        return true;
    }

    public static function dropForeignKey($table, $name)
    {
        $pfx = _DB_PREFIX_;
        $sql = "ALTER TABLE `{$pfx}{$table}` DROP FOREIGN KEY `{$name}`";

        \Db::getInstance()->execute($sql);

        return true;
    }
}
