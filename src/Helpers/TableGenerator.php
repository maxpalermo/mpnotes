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

class TableGenerator
{
    private $errors = [];

    /**
     * Crea una tabella nel database basata sulla definizione di un ObjectModel
     * 
     * @param array $definition Definizione dell'ObjectModel
     *
     * @return bool True se successo, false altrimenti
     */
    public function createTable(array $definition): bool
    {
        $sql = $this->generateFromModel($definition);

        if ($sql === false) {
            return false;
        }

        try {
            $success = \Db::getInstance()->execute($sql['main_table']);

            if ($success && $sql['lang_table']) {
                $success = \Db::getInstance()->execute($sql['lang_table']);
            }

            if (!$success) {
                $this->errors[] = \Db::getInstance()->getMsgError();
            }

            return $success;
        } catch (\PrestaShopDatabaseException $e) {
            $this->errors[] = $e->getMessage();

            return false;
        }
    }

    /**
     * Genera il SQL per creare una tabella basata sulla definizione di un ObjectModel
     * 
     * @param array $definition Definizione dell'ObjectModel
     *
     * @return array|false Array con SQL generato o false in caso di errore
     */
    public function generateFromModel(array $definition)
    {
        if (empty($definition['table']) || empty($definition['fields'])) {
            $this->errors[] = 'Definizione non valida';

            return false;
        }

        $result = [
            'main_table' => $this->generateMainTableSql($definition),
            'lang_table' => null,
        ];

        if (!empty($definition['multilang']) && $definition['multilang'] === true) {
            $result['lang_table'] = $this->generateLangTableSql($definition);
        }

        return $result;
    }

    /**
     * Esegue effettivamente la creazione delle tabelle nel database
     * 
     * @param array $definition Definizione dell'ObjectModel
     *
     * @return bool True se successo, false altrimenti
     */
    public function createTablesFromModel(array $definition): bool
    {
        $sql = $this->generateFromModel($definition);

        if ($sql === false) {
            return false;
        }

        try {
            $success = \Db::getInstance()->execute($sql['main_table']);

            if ($success && $sql['lang_table']) {
                $success = \Db::getInstance()->execute($sql['lang_table']);
            }

            if (!$success) {
                $this->errors[] = \Db::getInstance()->getMsgError();
            }

            return $success;
        } catch (\PrestaShopDatabaseException $e) {
            $this->errors[] = $e->getMessage();

            return false;
        }
    }

    protected function generateMainTableSql(array $definition): string
    {
        $tableName = _DB_PREFIX_ . $definition['table'];
        $primaryKey = $definition['primary'];
        $fields = $definition['fields'];
        $sqlFields = [];

        $primaryField = [
            $primaryKey => [
                'type' => \ObjectModelCore::TYPE_INT,
                'validate' => 'isUnsignedId',
                'autoincrement' => true,
            ],
        ];

        $fields = array_merge($primaryField, $fields);

        foreach ($fields as $fieldName => $fieldConfig) {
            if (!empty($fieldConfig['lang']) && $fieldConfig['lang'] === true) {
                continue;
            }

            $sqlFields[] = $this->generateFieldSql($fieldName, $fieldConfig);
        }

        $sql = "CREATE TABLE IF NOT EXISTS `$tableName` (\n  " .
            implode(",\n  ", $sqlFields) . ",\n  " .
            "PRIMARY KEY (`$primaryKey`)";

        $indexes = $this->generateIndexes($definition);
        if (!empty($indexes)) {
            $sql .= ",\n  " . implode(",\n  ", $indexes);
        }

        $sql .= "\n) ENGINE=" . ($definition['engine'] ?? 'InnoDB') .
            ' DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;';

        return $sql;
    }

    protected function generateLangTableSql(array $definition): string
    {
        $tableName = _DB_PREFIX_ . $definition['table'] . '_lang';
        $primaryKey = $definition['primary'];
        $fields = $definition['fields'];
        $sqlFields = [
            "`{$primaryKey}` int(10) UNSIGNED NOT NULL",
            '`id_lang` int(10) UNSIGNED NOT NULL',
        ];

        foreach ($fields as $fieldName => $fieldConfig) {
            if (empty($fieldConfig['lang']) || $fieldConfig['lang'] !== true) {
                continue;
            }

            $sqlFields[] = $this->generateFieldSql($fieldName, $fieldConfig);
        }

        $sql = "CREATE TABLE IF NOT EXISTS `$tableName` (\n  " .
            implode(",\n  ", $sqlFields) . ",\n  " .
            "PRIMARY KEY (`{$primaryKey}`, `id_lang`)" .
            "\n) ENGINE=" . ($definition['engine'] ?? 'InnoDB') .
            ' DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;';

        return $sql;
    }

    protected function generateFieldSql(string $fieldName, array $fieldConfig): string
    {
        $sql = "`$fieldName` ";

        switch ($fieldConfig['type']) {
            case \ObjectModelCore::TYPE_INT:
                $sql .= 'int(10)';

                break;
            case \ObjectModelCore::TYPE_BOOL:
                $sql .= 'tinyint(1)';

                break;
            case \ObjectModelCore::TYPE_FLOAT:
                $sql .= 'decimal(20,6)';

                break;
            case \ObjectModelCore::TYPE_DATE:
                $sql .= 'datetime';

                break;
            case \ObjectModelCore::TYPE_STRING:
                if (empty($fieldConfig['size'])) {
                    $sql .= 'json';
                } elseif ($fieldConfig['size'] > 255) {
                    $sql .= 'text';
                } else {
                    $sql .= 'varchar(' . (int) $fieldConfig['size'] . ')';
                }

                break;
            default:
                $sql .= 'text';
        }

        if (!empty($fieldConfig['required']) && $fieldConfig['required'] === true) {
            $sql .= ' NOT NULL';
        } else {
            $sql .= ' NULL';
        }

        if (isset($fieldConfig['default'])) {
            $default = is_string($fieldConfig['default'])
                ? "'" . pSQL($fieldConfig['default']) . "'"
                : (int) $fieldConfig['default'];
            $sql .= ' DEFAULT ' . $default;
        }

        if (isset($fieldConfig['autoincrement']) && $fieldConfig['autoincrement'] === true) {
            $sql .= ' AUTO_INCREMENT';
        }

        return $sql;
    }

    protected function generateIndexes(array $definition): array
    {
        $indexes = [];
        $primaryKey = $definition['primary'];
        $fields = $definition['fields'];

        foreach ($fields as $fieldName => $fieldConfig) {
            if (strpos($fieldName, 'id_') === 0 && $fieldName !== $primaryKey) {
                $indexType = $fieldConfig['index'] ?? 'INDEX';
                $indexes[] = "$indexType `$fieldName` (`$fieldName`)";
            }
        }

        if (!empty($definition['indexes'])) {
            foreach ($definition['indexes'] as $indexName => $indexFields) {
                $indexType = is_array($indexFields) ? ($indexFields['type'] ?? 'INDEX') : 'INDEX';
                $fieldsList = is_array($indexFields) ? $indexFields['fields'] : $indexFields;

                if (is_array($fieldsList)) {
                    $fieldsList = implode('`,`', $fieldsList);
                }

                $indexes[] = "$indexType `$indexName` (`$fieldsList`)";
            }
        }

        return $indexes;
    }

    /**
     * Restituisce gli errori verificatisi durante l'ultima operazione
     * 
     * @return array Lista degli errori
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    /**
     * Verifica se una stringa è un JSON valido
     * 
     * @param string $jsonString La stringa da verificare
     *
     * @return bool True se valido, false altrimenti
     */
    public static function isJson(string $jsonString): bool
    {
        $jsonString = trim($jsonString);
        if ($jsonString === '') {
            return false;
        }

        if (PHP_VERSION_ID >= 80300) {
            return json_validate($jsonString);
        }

        try {
            json_decode($jsonString, true, 512, JSON_THROW_ON_ERROR);

            return true;
        } catch (\JsonException) {
            return false;
        }
    }
}
