<?php

/*
 * Copyright (c) 2017 Heimrich & Hannot GmbH
 *
 * @license LGPL-3.0+
 */

namespace HeimrichHannot\CategoriesBundle\Backend;

use Contao\Backend;
use HeimrichHannot\CategoriesBundle\Model\CategorySourceModel;

class CategoryConfig extends Backend
{
    public static function getSourcesAsOptions()
    {
        $options = [];

        if (null !== ($sources = CategorySourceModel::findAll())) {
            $options = $sources->fetchEach('title');
        }

        asort($options);

        return array_combine($options, $options);
    }
}
