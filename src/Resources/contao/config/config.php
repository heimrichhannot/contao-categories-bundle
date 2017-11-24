<?php

/**
 * Backend modules
 */
$GLOBALS['BE_MOD']['content']['categories'] = [
    'tables' => ['tl_category', 'tl_category_config', 'tl_category_source']
];

/**
 * Permissions
 */
$GLOBALS['TL_PERMISSIONS'][] = 'categories';
$GLOBALS['TL_PERMISSIONS'][] = 'categoriep';

/**
 * Models
 */
$GLOBALS['TL_MODELS']['tl_category']        = '\HeimrichHannot\CategoriesBundle\Model\CategoryModel';
$GLOBALS['TL_MODELS']['tl_category_config'] = '\HeimrichHannot\CategoriesBundle\Model\CategoryConfigModel';
$GLOBALS['TL_MODELS']['tl_category_source'] = '\HeimrichHannot\CategoriesBundle\Model\CategorySourceModel';