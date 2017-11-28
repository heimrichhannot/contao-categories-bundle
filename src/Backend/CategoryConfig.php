<?php

/*
 * Copyright (c) 2017 Heimrich & Hannot GmbH
 *
 * @license LGPL-3.0+
 */

namespace HeimrichHannot\CategoriesBundle\Backend;

use Contao\Backend;
use HeimrichHannot\CategoriesBundle\Model\CategoryContextModel;

class CategoryConfig extends Backend
{
    public static function getContextsAsOptions()
    {
        $options = [];

        if (null !== ($contexts = CategoryContextModel::findAll())) {
            $options = $contexts->fetchEach('title');
        }

        asort($options);

        return array_combine($options, $options);
    }
}
