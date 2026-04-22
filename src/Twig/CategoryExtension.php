<?php

/*
 * Copyright (c) 2021 Heimrich & Hannot GmbH
 *
 * @license LGPL-3.0-or-later
 */

namespace HeimrichHannot\CategoriesBundle\Twig;

use Contao\StringUtil;
use Contao\System;
use HeimrichHannot\CategoriesBundle\Twig\Runtime\CategoriesRuntime;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class CategoryExtension extends AbstractExtension
{
    public function getFilters()
    {
        return [
            new TwigFilter('category', [CategoriesRuntime::class, 'getCategory']),
            new TwigFilter('contextualCategory', [CategoriesRuntime::class, 'getContextualCategory']),
            new TwigFilter('categories', [CategoriesRuntime::class, 'getCategories']),
        ];
    }
}
