<?php

/*
 * Copyright (c) 2022 Heimrich & Hannot GmbH
 *
 * @license LGPL-3.0-or-later
 */

use HeimrichHannot\CategoriesBundle\Backend\Category;

$dca = &$GLOBALS['TL_DCA']['tl_form_field'];

$dca['palettes']['categoryTree'] = '{type_legend},type;{fconfig_legend},huh_categories;';

Category::addMultipleCategoriesFieldToDca('tl_form_field', 'huh_categories');
