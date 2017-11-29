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

/**
 * Backend form fields
 */
$GLOBALS['BE_FFL']['categoryTree'] = 'HeimrichHannot\CategoriesBundle\Widget\CategoryTree';

/**
 * Hooks
 */
$GLOBALS['TL_HOOKS']['executePostActions']['reloadCategoryTree'] = ['HeimrichHannot\CategoriesBundle\Backend\Category', 'reloadCategoryTree'];
$GLOBALS['TL_HOOKS']['parseBackendTemplate']['adjustCategoryTree'] = ['HeimrichHannot\CategoriesBundle\Backend\Category', 'adjustCategoryTree'];

/**
 * Assets
 */
if (\HeimrichHannot\Haste\Util\Container::isBackend()) {
    $GLOBALS['TL_CSS']['contao-categories-bundle'] = 'bundles/categories/css/contao-categories-bundle.be.css|static';
}

/**
 * Models
 */
$GLOBALS['TL_MODELS']['tl_category']             = 'HeimrichHannot\CategoriesBundle\Model\CategoryModel';
$GLOBALS['TL_MODELS']['tl_category_config']      = 'HeimrichHannot\CategoriesBundle\Model\CategoryConfigModel';
$GLOBALS['TL_MODELS']['tl_category_context']     = 'HeimrichHannot\CategoriesBundle\Model\CategoryContextModel';
$GLOBALS['TL_MODELS']['tl_category_association'] = 'HeimrichHannot\CategoriesBundle\Model\CategoryAssociationModel';

/**
 * Permissions
 */
$GLOBALS['TL_PERMISSIONS'][] = 'categories';
$GLOBALS['TL_PERMISSIONS'][] = 'categoriep';