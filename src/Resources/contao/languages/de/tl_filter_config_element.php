<?php

/*
 * Copyright (c) 2020 Heimrich & Hannot GmbH
 *
 * @license LGPL-3.0-or-later
 */

$lang = &$GLOBALS['TL_LANG']['tl_filter_config_element'];

$lang['parentCategories'] = ['Elternkategorien', 'Wählen Sie hier die Elternkategorien der Kategorien aus, welche im Filter gewählt werden können.'];

$lang['reference']['type'][\HeimrichHannot\CategoriesBundle\Filter\Type\CategoryChoiceType::TYPE] = 'Kategorien';
$lang['reference']['type'][\HeimrichHannot\CategoriesBundle\Filter\Type\ParentCategoryChoiceType::TYPE] = 'Eltern-Kategorien';
