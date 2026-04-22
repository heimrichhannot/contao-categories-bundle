<?php

/*
 * Copyright (c) 2026 Heimrich & Hannot GmbH
 *
 * @license LGPL-3.0-or-later
 */

namespace HeimrichHannot\CategoriesBundle\EventListener;

use Contao\CoreBundle\DependencyInjection\Attribute\AsHook;
use Contao\Database;
use Contao\DataContainer;

class DateAddedListener
{
    #[AsHook('loadDataContainer')]
    public function onLoadDataContainer(string $table): void
    {
        if (!in_array($table, ['tl_category', 'tl_category_config', 'tl_category_context'])) {
            return;
        }

        // Register onsubmit callback to set dateAdded when saving
        $GLOBALS['TL_DCA'][$table]['config']['onsubmit_callback'][] = [self::class, 'setDateAddedOnSubmit'];

        // Register oncopy callback to set new dateAdded when copying
        $GLOBALS['TL_DCA'][$table]['config']['oncopy_callback'][] = [self::class, 'setDateAddedOnCopy'];
    }

    public function setDateAddedOnSubmit(DataContainer $dc): void
    {
        if (!$dc->id || !$dc->table) {
            return;
        }

        // Check if dateAdded is already set
        $result = Database::getInstance()
            ->prepare("SELECT dateAdded FROM {$dc->table} WHERE id=?")
            ->execute($dc->id);

        // Only set dateAdded if it's 0 (not set yet)
        if ($result->numRows && $result->dateAdded == 0) {
            Database::getInstance()
                ->prepare("UPDATE {$dc->table} SET dateAdded=? WHERE id=?")
                ->execute(time(), $dc->id);
        }
    }

    public function setDateAddedOnCopy(int $insertId, DataContainer $dc): void
    {
        if (!$insertId || !$dc->table) {
            return;
        }

        // When copying, always set a new dateAdded timestamp
        Database::getInstance()
            ->prepare("UPDATE {$dc->table} SET dateAdded=? WHERE id=?")
            ->execute(time(), $insertId);
    }
}

