<?php

/*
 * Copyright (c) 2021 Heimrich & Hannot GmbH
 *
 * @license LGPL-3.0-or-later
 */

namespace HeimrichHannot\CategoriesBundle\Backend;

use Contao\Backend;
use Contao\DataContainer;
use HeimrichHannot\CategoriesBundle\Model\CategoryContextModel;

class CategoryConfig extends Backend
{
    public static function getContextsAsOptions()
    {
        $options = [];

        if (null !== ($contexts = CategoryContextModel::findAll())) {
            $options = array_combine($contexts->fetchEach('id'), $contexts->fetchEach('title'));
        }

        asort($options);

        return $options;
    }

    public static function deleteCachedPropertyValuesByCategoryAndContext($value, DataContainer $dc)
    {
        if (null !== ($config = \System::getContainer()->get('huh.categories.config_manager')->findOneBy('id', $dc->id))) {
            $valueOld = $config->context;

            if ($value != $valueOld) {
                \System::getContainer()->get('huh.categories.property_cache_manager')->delete(
                    [
                        'category=?',
                        '(context=? OR context=?)',
                    ], [
                        $config->pid,
                        $value,
                        $valueOld,
                    ]
                );
            }
        }

        return $value;
    }
}
