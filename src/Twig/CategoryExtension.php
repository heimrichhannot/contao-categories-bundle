<?php
/**
 * Copyright (c) 2018 Heimrich & Hannot GmbH
 *
 * @author Rico Kaltofen <r.kaltofen@heimrich-hannot.de>
 * @license http://www.gnu.org/licences/lgpl-3.0.html LGPL
 */

namespace HeimrichHannot\CategoriesBundle\Twig;


use Contao\StringUtil;
use Contao\System;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class CategoryExtension extends AbstractExtension
{
    public function getFilters()
    {
        return [
            new TwigFilter('category', [$this, 'getCategory']),
            new TwigFilter('categories', [$this, 'getCategories']),
        ];
    }

    /**
     * Get the category for a given category id
     * @param int $id
     * @return array|null
     */
    public function getCategory($id)
    {
        $category = System::getContainer()->get('huh.categories.manager')->findByIdOrAlias($id);

        if (null === $category) {
            return null;
        }

        return $category->row();
    }

    /**
     * Get the category for a given category id
     * @param string|array $ids
     * @return array|null
     */
    public function getCategories($ids)
    {
        $ids = StringUtil::deserialize($ids, true);

        $categories = System::getContainer()->get('huh.categories.manager')->findMultipleByIds($ids);

        if (null === $categories) {
            return null;
        }

        return $categories->fetchAll();
    }
}