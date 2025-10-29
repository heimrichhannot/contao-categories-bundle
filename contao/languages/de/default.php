<?php

/*
 * Copyright (c) 2022 Heimrich & Hannot GmbH
 *
 * @license LGPL-3.0-or-later
 */

use HeimrichHannot\CategoriesBundle\Flare\FilterElement\ParentCategoryFilterElement;

$GLOBALS['TL_LANG']['MSC']['categoriesBundle'] = [
    'configsAvailable' => 'Konfigurationen verfügbar',
    'primaryCategory' => 'Primäre Kategorie',
];

/*
 * Miscellaneous
 */
$GLOBALS['TL_LANG']['MSC']['cm_resetCategories'] = ['Alle Kategorien', 'Zeigt Nachrichten aus allen Kategorien'];
$GLOBALS['TL_LANG']['MSC']['categoryPicker'] = 'Kategorien';

$flare['filter'][ParentCategoryFilterElement::TYPE][0] = 'Elternkategorie (Kindkategorien der ausgewählten Kategorie)';
$flare['filter'][ParentCategoryFilterElement::TYPE][1] = 'Filtert Einträge nach den Kindkategorien der ausgewählten Kategorie.';