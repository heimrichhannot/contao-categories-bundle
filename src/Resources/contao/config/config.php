<?php

/**
 * Backend modules
 */
$GLOBALS['BE_MOD']['content']['categories'] = [
    'tables' => ['tl_category']
];

/**
 * Permissions
 */
$GLOBALS['TL_PERMISSIONS'][] = 'categories';
$GLOBALS['TL_PERMISSIONS'][] = 'categoriep';

/**
 * Models
 */
$GLOBALS['TL_MODELS']['tl_category'] = '\HeimrichHannot\CategoriesBundle\Model\CategoryModel';