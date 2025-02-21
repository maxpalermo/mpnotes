<?php
/**
 * 2007-2025 PrestaShop
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License (AFL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/afl-3.0.php
 *
 * @author    Massimiliano Palermo
 * @copyright 2007-2025 PrestaShop SA
 * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 */

declare(strict_types=1);

namespace MpSoft\MpNotes\Helpers;

class InstallTab
{
    /**
     * @var array Default configuration for tabs
     */
    private array $tabs;

    /**
     * @var string Module name
     */
    private string $module;

    /**
     * @var int Parent tab id
     */
    private int $id_parent;

    /**
     * InstallTab constructor.
     *
     * @param string $module Module name
     * @param array $tabs Tabs configuration
     * @param int $id_parent Parent tab id
     */
    public function __construct(string $module, array $tabs = [], int $id_parent = 0)
    {
        $this->module = $module;
        $this->tabs = $tabs;
        $this->id_parent = $id_parent;
    }

    /**
     * Install module tabs
     *
     * @return bool
     *
     * @throws \PrestaShopException
     * @throws \PrestaShopDatabaseException
     */
    public function installTabs(): bool
    {
        if (empty($this->tabs)) {
            return true;
        }

        $languages = \Language::getLanguages(false);

        foreach ($this->tabs as $tabData) {
            try {
                $tab = new \Tab();
                $tab->active = $tabData['active'] ?? true;
                $tab->class_name = $tabData['class_name'];
                $tab->module = $this->module;
                $tab->id_parent = !$this->id_parent ? $this->getTabId($tabData['parent_class_name'] ?? 'IMPROVE') : $this->id_parent;
                $tab->icon = $tabData['icon'] ?? '';
                $tab->position = $tabData['position'] ?? 0;
                $tab->route_name = $tabData['route_name'] ?? '';
                $tab->hide_host_mode = $tabData['hide_host_mode'] ?? 0;
                $tab->wording_domain = $tabData['wording_domain'] ?? '';
                $tab->wording = $tabData['wording'] ?? '';

                foreach ($languages as $language) {
                    $tab->name[$language['id_lang']] = $tabData['name'];
                }

                if (!$tab->add()) {
                    throw new \PrestaShopException(sprintf('Failed to install tab: %s', $tabData['class_name']));
                }

                if (isset($tabData['children']) && is_array($tabData['children'])) {
                    $id_parent = (int) $tab->id;
                    $childTabs = new self($this->module, $tabData['children'], $id_parent);
                    if (!$childTabs->installTabs()) {
                        throw new \PrestaShopException(sprintf('Failed to install child tabs for: %s', $tabData['class_name']));
                    }
                }
            } catch (\Exception $e) {
                throw new \PrestaShopException(sprintf('Error installing tab %s: %s', $tabData['class_name'], $e->getMessage()));
            }
        }

        return true;
    }

    /**
     * Uninstall module tabs
     *
     * @return bool
     *
     * @throws \PrestaShopDatabaseException
     * @throws \PrestaShopException
     */
    public function uninstallTabs(): bool
    {
        if (empty($this->tabs)) {
            return true;
        }

        foreach ($this->tabs as $tabData) {
            try {
                $idTab = $this->findTabId($tabData['class_name']);
                if ($idTab) {
                    $tab = new \Tab($idTab);
                    if (!$tab->delete()) {
                        throw new \PrestaShopException(sprintf('Failed to uninstall tab: %s', $tabData['class_name']));
                    }
                }

                if (isset($tabData['children']) && is_array($tabData['children'])) {
                    $childTabs = new self($this->module, $tabData['children']);
                    if (!$childTabs->uninstallTabs()) {
                        throw new \PrestaShopException(sprintf('Failed to uninstall child tabs for: %s', $tabData['class_name']));
                    }
                }
            } catch (\Exception $e) {
                throw new \PrestaShopException(sprintf('Error uninstalling tab %s: %s', $tabData['class_name'], $e->getMessage()));
            }
        }

        return true;
    }

    /**
     * Get tab ID from class name using PrestaShopCollection
     *
     * @param string $className
     *
     * @return int
     */
    private function getTabId(string $className): int
    {
        $tabId = $this->findTabId($className);

        return $tabId ?: $this->findTabId('AdminParentModules');
    }

    /**
     * Find tab ID using PrestaShopCollection
     *
     * @param string $className
     *
     * @return int
     */
    public static function findTabId(string $className): int
    {
        if ($className === -1) {
            return -1;
        }

        $collection = new \PrestaShopCollection('Tab');
        $collection->where('class_name', '=', $className);

        /** @var \Tab $tab */
        $tab = $collection->getFirst();

        return $tab ? (int) $tab->id : 0;
    }

    /**
     * Check if a tab exists
     *
     * @param string $className
     *
     * @return bool
     */
    public function tabExists(string $className): bool
    {
        return (bool) $this->findTabId($className);
    }
}
