<?php

/*
 * Copyright (c) 2020 Heimrich & Hannot GmbH
 *
 * @license LGPL-3.0-or-later
 */

/*
 * Extend the tl_page palettes
 */
$GLOBALS['TL_DCA']['tl_page']['palettes']['root'] = str_replace(
    '{sitemap_legend',
    '{categoriesParam_legend:hide},categoriesParam;{sitemap_legend',
    $GLOBALS['TL_DCA']['tl_page']['palettes']['root']
);

if (isset($arrDca['palettes']['rootfallback'])) {
    $arrDca['palettes']['rootfallback'] = str_replace(
        '{sitemap_legend',
        '{categoriesParam_legend:hide},categoriesParam;{sitemap_legend',
        $GLOBALS['TL_DCA']['tl_page']['palettes']['root']
    );
}

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
