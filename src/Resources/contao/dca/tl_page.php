<?php

/*
 * Copyright (c) 2022 Heimrich & Hannot GmbH
 *
 * @license LGPL-3.0-or-later
 */

/*
 * Extend the tl_page palettes
 */

use Contao\CoreBundle\DataContainer\PaletteManipulator;

PaletteManipulator::create()
    ->addLegend('categories_legend', 'sitemap_legend', PaletteManipulator::POSITION_AFTER, true)
    ->addField('categoriesParam', 'categories_legend', PaletteManipulator::POSITION_APPEND)
    ->applyToPalette('root', 'tl_page')
    ->applyToPalette('rootfallback', 'tl_page');

/*
 * Add fields to tl_page
 */
$GLOBALS['TL_DCA']['tl_page']['fields']['categoriesParam'] = [
    'label' => &$GLOBALS['TL_LANG']['tl_page']['categoriesParam'],
    'exclude' => true,
    'inputType' => 'text',
    'eval' => ['maxlength' => 64, 'rgxp' => 'alias', 'tl_class' => 'w50'],
    'sql' => "varchar(64) NOT NULL default ''",
];
