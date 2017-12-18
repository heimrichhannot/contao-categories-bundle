<?php

$dca = &$GLOBALS['TL_DCA']['tl_module'];

/**
 * Selectors
 */
$dca['palettes']['__selector__'][] = 'cm_customCategories';
$dca['palettes']['__selector__'][] = 'cm_customRoot';

/**
 * Palettes
 */
$dca['palettes']['categoriesMenu'] = '{title_legend},name,headline,type;{config_legend},cm_resetCategories,cm_customRoot,cm_customCategories;{redirect_legend:hide},jumpTo;{template_legend:hide},navigationTpl,customTpl;{protected_legend:hide},protected;{expert_legend:hide},guests,cssID,space';

/**
 * Subpalettes
 */
$dca['subpalettes']['cm_customCategories'] = 'cm_categories';
$dca['subpalettes']['cm_customRoot']       = 'cm_categoriesRoot';

/**
 * Fields
 */
$arrFields = [
    'cm_customCategories' => [
        'label'     => &$GLOBALS['TL_LANG']['tl_module']['cm_customCategories'],
        'exclude'   => true,
        'inputType' => 'checkbox',
        'eval'      => ['submitOnChange' => true, 'tl_class' => 'clr'],
        'sql'       => "char(1) NOT NULL default ''",
    ],
    'cm_customRoot'       => [
        'label'     => &$GLOBALS['TL_LANG']['tl_module']['cm_customRoot'],
        'exclude'   => true,
        'inputType' => 'checkbox',
        'eval'      => ['submitOnChange' => true, 'tl_class' => 'clr'],
        'sql'       => "char(1) NOT NULL default ''",
    ],
    'cm_categoriesRoot'   => \HeimrichHannot\CategoriesBundle\Backend\Category::getCategoryFieldDca(),
    'cm_resetCategories'  => [
        'label'     => &$GLOBALS['TL_LANG']['tl_module']['cm_resetCategories'],
        'exclude'   => true,
        'inputType' => 'checkbox',
        'eval'      => ['tl_class' => 'w50'],
        'sql'       => "char(1) NOT NULL default ''",
    ],
];

$dca['fields'] = array_merge($dca['fields'], $arrFields);

// this call automatically adds the field "<categoriesFieldname>_primary" which is a simple integer field that contains the reference to the category marked as primary
\HeimrichHannot\CategoriesBundle\Backend\Category::addMultipleCategoriesFieldToDca('tl_module', 'cm_categories', [
    'addPrimaryCategory' => false,
]);