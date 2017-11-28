<?php

/*
 * Copyright (c) 2017 Heimrich & Hannot GmbH
 *
 * @license LGPL-3.0+
 */

namespace HeimrichHannot\CategoriesBundle\Backend;

use Contao\Backend;

class CategoryContext extends Backend
{
    const CATEGORY_FIELD_CONTEXT_MAPPING_FIELD = 'categoryFieldContextMapping';

    public static function addFieldContextMappingFieldToDca($table, $categoryFieldTable, $label = null)
    {
        \System::loadLanguageFile('tl_category_context');
        \Controller::loadDataContainer($table);

        $label = $label ?: $GLOBALS['TL_LANG']['tl_category_context'][static::CATEGORY_FIELD_CONTEXT_MAPPING_FIELD];

        $GLOBALS['TL_DCA'][$table]['fields'][static::CATEGORY_FIELD_CONTEXT_MAPPING_FIELD] = [
            'label' => &$label,
            'exclude' => true,
            'inputType' => 'multiColumnEditor',
            'eval' => [
                'tl_class' => 'long clr',
                'multiColumnEditor' => [
                    'minRowCount' => 0,
                    'fields' => [
                        'field' => [
                            'label' => &$GLOBALS['TL_LANG']['tl_category_context']['field'],
                            'inputType' => 'select',
                            'options' => static::getCategoryFieldsAsOptions($categoryFieldTable),
                            'eval' => ['tl_class' => 'w50', 'mandatory' => true, 'includeBlankOption' => true],
                        ],
                        'context' => [
                            'label' => &$GLOBALS['TL_LANG']['tl_category_context']['context'],
                            'inputType' => 'select',
                            'options_callback' => ['HeimrichHannot\CategoriesBundle\Backend\CategoryConfig', 'getContextsAsOptions'],
                            'eval' => ['mandatory' => true, 'includeBlankOption' => true],
                        ],
                    ],
                ],
            ],
            'sql' => 'blob NULL',
        ];
    }

    public static function getCategoryFieldsAsOptions($categoryFieldTable)
    {
        \Controller::loadDataContainer($categoryFieldTable);

        $options = [];

        foreach ($GLOBALS['TL_DCA'][$categoryFieldTable]['fields'] as $field => $data) {
            if (isset($data['eval']['isCategoryField']) && $data['eval']['isCategoryField'] ||
                isset($data['eval']['isCategoriesField']) && $data['eval']['isCategoriesField']
            ) {
                $options[] = $field;
            }
        }

        asort($options);

        return $options;
    }
}
