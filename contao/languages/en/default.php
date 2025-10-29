<?php

/*
 * Copyright (c) 2022 Heimrich & Hannot GmbH
 *
 * @license LGPL-3.0-or-later
 */

use HeimrichHannot\CategoriesBundle\Flare\FilterElement\ParentCategoryFilterElement;

$GLOBALS['TL_LANG']['MSC']['categoriesBundle'] = [
    'configsAvailable' => 'Configuration available',
    'primaryCategory' => 'Primary category',
];

/*
 * Miscellaneous
 */
$GLOBALS['TL_LANG']['MSC']['cm_resetCategories'] = ['All categories', 'Show news from all categories'];
$GLOBALS['TL_LANG']['MSC']['categoryPicker'] = 'Categories';

$GLOBALS['TL_LANG']['FLARE']['filter'][ParentCategoryFilterElement::TYPE][0] = 'Parent Category (child categories of the selected category)';
$GLOBALS['TL_LANG']['FLARE']['filter'][ParentCategoryFilterElement::TYPE][1] = 'Filters entries by the child categories of the selected category.';
