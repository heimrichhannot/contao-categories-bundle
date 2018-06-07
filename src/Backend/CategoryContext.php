<?php

/*
 * Copyright (c) 2017 Heimrich & Hannot GmbH
 *
 * @license LGPL-3.0+
 */

namespace HeimrichHannot\CategoriesBundle\Backend;

use Contao\Backend;
use Contao\Controller;
use Contao\DataContainer;
use Contao\StringUtil;
use Contao\System;

class CategoryContext extends Backend
{
    const CATEGORY_FIELD_CONTEXT_MAPPING_FIELD = 'categoryFieldContextMapping';

    public static function addFieldContextMappingFieldToDca($table, $categoryFieldTable, $label = null)
    {
        System::loadLanguageFile('tl_category_context');
        Controller::loadDataContainer($table);

        $label = $label ?: $GLOBALS['TL_LANG']['tl_category_context'][static::CATEGORY_FIELD_CONTEXT_MAPPING_FIELD];

        $GLOBALS['TL_DCA'][$table]['fields'][static::CATEGORY_FIELD_CONTEXT_MAPPING_FIELD] = [
            'label' => &$label,
            'exclude' => true,
            'inputType' => 'multiColumnEditor',
            'save_callback' => [['HeimrichHannot\CategoriesBundle\Backend\CategoryContext', 'deleteCachedPropertyValuesByFieldOrContext']],
            'eval' => [
                'tl_class' => 'long clr',
                'multiColumnEditor' => [
                    'minRowCount' => 0,
                    'fields' => [
                        'categoryField' => [
                            'label' => &$GLOBALS['TL_LANG']['tl_category_context']['categoryField'],
                            'inputType' => 'select',
                            'options' => static::getCategoryFieldsAsOptions($categoryFieldTable),
                            'eval' => ['tl_class' => 'w50', 'mandatory' => true, 'includeBlankOption' => true, 'groupStyle' => 'width: 200px'],
                        ],
                        'context' => [
                            'label' => &$GLOBALS['TL_LANG']['tl_category_context']['context'],
                            'inputType' => 'select',
                            'options_callback' => ['HeimrichHannot\CategoriesBundle\Backend\CategoryConfig', 'getContextsAsOptions'],
                            'eval' => ['mandatory' => true, 'includeBlankOption' => true, 'groupStyle' => 'width: 200px'],
                        ],
                    ],
                ],
            ],
            'sql' => 'blob NULL',
        ];
    }

    public static function deleteCachedPropertyValuesByFieldOrContext($value, DataContainer $dc)
    {
        if (null !== ($categoryContext = System::getContainer()->get('huh.categories.context_manager')->findOneBy('id', $dc->id))) {
            $valueOld = $categoryContext->{$dc->field};

            if ($value != $valueOld) {
                $fields = [];
                $contexts = [];

                // collect relevant combinations
                foreach (StringUtil::deserialize($valueOld, true) as $mapping) {
                    $fields[] = '"'.$mapping['categoryField'].'"';
                    $contexts[] = $mapping['context'];
                }

                foreach (StringUtil::deserialize($value, true) as $mapping) {
                    $fields[] = '"'.$mapping['categoryField'].'"';
                    $contexts[] = $mapping['context'];
                }

                $fields = array_unique($fields);
                $contexts = array_unique($contexts);

                System::getContainer()->get('huh.categories.property_cache_manager')->delete(
                    [
                        'categoryField IN ('.implode(',', $fields).') OR context IN ('.implode(',', $contexts).')',
                    ], []
                );
            }
        }

        return $value;
    }

    public static function deleteCachedPropertyValuesByContext($value, DataContainer $dc)
    {
        if (System::getContainer()->get('huh.utils.model')->hasValueChanged($value, $dc)) {
            $fields = [];
            $contexts = [];

            $valueOld = System::getContainer()->get('huh.utils.model')->getModelInstanceFieldValue($dc->field, $dc->table, $dc->id);

            // collect relevant combinations
            foreach (StringUtil::deserialize($valueOld, true) as $mapping) {
                $fields[] = '"'.$mapping['categoryField'].'"';
                $contexts[] = $mapping['context'];
            }

            foreach (StringUtil::deserialize($value, true) as $mapping) {
                $fields[] = '"'.$mapping['categoryField'].'"';
                $contexts[] = $mapping['context'];
            }

            $fields = array_unique($fields);
            $contexts = array_unique($contexts);

            System::getContainer()->get('huh.categories.property_cache_manager')->delete(
                [
                    'categoryField IN ('.implode(',', $fields).') OR context IN ('.implode(',', $contexts).')',
                ], []
            );
        }

        return $value;
    }

    public static function getCategoryFieldsAsOptions($categoryFieldTable)
    {
        Controller::loadDataContainer($categoryFieldTable);

        $options = [];

        foreach ($GLOBALS['TL_DCA'][$categoryFieldTable]['fields'] as $field => $data) {
            if (isset($data['eval']['isCategoryField']) && $data['eval']['isCategoryField'] ||
                isset($data['eval']['isCategoriesField']) && $data['eval']['isCategoriesField']
            ) {
                $options[] = $field;
            }
        }

        asort($options);

        return array_combine($options, $options);
    }
}
