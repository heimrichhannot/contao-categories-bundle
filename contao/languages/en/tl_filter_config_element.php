<?php

/*
 * Copyright (c) 2020 Heimrich & Hannot GmbH
 *
 * @license LGPL-3.0-or-later
 */

$lang = &$GLOBALS['TL_LANG']['tl_filter_config_element'];

$lang['parentCategories'] = ['Parent categories', 'Choose parent categories of the categories, which should be available in the filter.'];

$lang['reference']['type'][\HeimrichHannot\CategoriesBundle\Filter\Type\CategoryChoiceType::TYPE] = 'Categories';
$lang['reference']['type'][\HeimrichHannot\CategoriesBundle\Filter\Type\ParentCategoryChoiceType::TYPE] = 'Parent categories';
