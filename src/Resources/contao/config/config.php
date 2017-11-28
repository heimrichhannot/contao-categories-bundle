<?php

/**
 * Backend modules
 */
$GLOBALS['BE_MOD']['content']['categories'] = [
    'tables' => ['tl_category', 'tl_category_config', 'tl_category_context']
];

/**
 * JavaScript
 */
if (\HeimrichHannot\Haste\Util\Container::isBackend()) {
    $GLOBALS['TL_JAVASCRIPT']['contao-categories-bundle'] =
        'bundles/categories/js/contao-categories-bundle.be.min.js|static';
}

$GLOBALS['BE_FFL']['treePicker']   = 'HeimrichHannot\CategoriesBundle\Widget\WidgetTreePicker';
$GLOBALS['BE_FFL']['treeSelector'] = 'HeimrichHannot\CategoriesBundle\Widget\WidgetTreeSelector';

/**
 * Models
 */
$GLOBALS['TL_MODELS']['tl_category']             = '\HeimrichHannot\CategoriesBundle\Model\CategoryModel';
$GLOBALS['TL_MODELS']['tl_category_config']      = '\HeimrichHannot\CategoriesBundle\Model\CategoryConfigModel';
$GLOBALS['TL_MODELS']['tl_category_context']     = '\HeimrichHannot\CategoriesBundle\Model\CategoryContextModel';
$GLOBALS['TL_MODELS']['tl_category_association'] = '\HeimrichHannot\CategoriesBundle\Model\CategoryAssociationModel';

/**
 * Permissions
 */
$GLOBALS['TL_PERMISSIONS'][] = 'categories';
$GLOBALS['TL_PERMISSIONS'][] = 'categoriep';