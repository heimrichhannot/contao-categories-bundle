<?php

/**
 * news_categories extension for Contao Open Source CMS
 *
 * Copyright (C) 2011-2014 Codefog
 *
 * @package news_categories
 * @author  Webcontext <http://webcontext.com>
 * @author  Codefog <info@codefog.pl>
 * @author  Kamil Kuzminski <kamil.kuzminski@codefog.pl>
 * @license LGPL
 */


/**
 * Extend the tl_page palettes
 */
$GLOBALS['TL_DCA']['tl_page']['palettes']['root'] = str_replace('adminEmail;', 'adminEmail;{categoriesParam_legend:hide},categoriesParam;', $GLOBALS['TL_DCA']['tl_page']['palettes']['root']);

/**
 * Add fields to tl_page
 */
$GLOBALS['TL_DCA']['tl_page']['fields']['categoriesParam'] = [
    'label'     => &$GLOBALS['TL_LANG']['tl_page']['categoriesParam'],
    'exclude'   => true,
    'inputType' => 'text',
    'eval'      => ['maxlength' => 64, 'rgxp' => 'alias', 'tl_class' => 'w50'],
    'sql'       => "varchar(64) NOT NULL default ''",
];
