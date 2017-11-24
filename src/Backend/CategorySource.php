<?php

/*
 * Copyright (c) 2017 Heimrich & Hannot GmbH
 *
 * @license LGPL-3.0+
 */

namespace HeimrichHannot\CategoriesBundle\Backend;

use Contao\Backend;

class CategorySource extends Backend
{
    const CATEGORY_FIELD_SOURCE_MAPPING_FIELD = 'categoryFieldSourceMapping';

    public static function addFieldSourceMappingFieldToDca($table, $categoryFieldTable, $label = null)
    {
        \System::loadLanguageFile('tl_category_source');
        \Controller::loadDataContainer($table);

        $label = $label ?: $GLOBALS['TL_LANG']['tl_category_source'][static::CATEGORY_FIELD_SOURCE_MAPPING_FIELD];

        $GLOBALS['TL_DCA'][$table]['fields'][static::CATEGORY_FIELD_SOURCE_MAPPING_FIELD] = [
            'label' => &$label,
            'exclude' => true,
            'inputType' => 'multiColumnEditor',
            'eval' => [
                'tl_class' => 'long clr',
                'multiColumnEditor' => [
                    'fields' => [
                        'field' => [
                            'label' => &$GLOBALS['TL_LANG']['tl_category_source']['field'],
                            'inputType' => 'select',
                            'options' => static::getCategoryFieldsAsOptions($categoryFieldTable),
                            'eval' => ['tl_class' => 'w50', 'mandatory' => true, 'includeBlankOption' => true],
                        ],
                        'source' => [
                            'label' => &$GLOBALS['TL_LANG']['tl_category_source']['source'],
                            'inputType' => 'select',
                            'foreignKey' => 'tl_category_source.title',
                            'relation' => ['type' => 'belongsTo', 'load' => 'eager'],
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
