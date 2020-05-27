<?php

/*
 * Copyright (c) 2020 Heimrich & Hannot GmbH
 *
 * @license LGPL-3.0-or-later
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
            new TwigFilter('contextualCategory', [$this, 'getContextualCategory']),
            new TwigFilter('categories', [$this, 'getCategories']),
        ];
    }

    /**
     * Get the category for a given category id.
     *
     * @param int $id
     *
     * @return array|null
     */
    public function getCategory($id)
    {
        $manager = System::getContainer()->get('huh.categories.manager');

        $category = $manager->findByIdOrAlias($id);

        if (null === $category) {
            return null;
        }

        return $category->row();
    }

    /**
     * Get the category for a given category id taking into account the contextual (overridable) properties -> see README.md for more detail.
     *
     * @param $id
     * @param $contextObj
     *
     * @return array|null
     */
    public function getContextualCategory($id, $contextObj, string $categoryField, int $primaryCategory, bool $skipCache = false)
    {
        $manager = System::getContainer()->get('huh.categories.manager');

        $category = $manager->findByIdOrAlias($id);

        if (null === $category) {
            return null;
        }

        $manager->addOverridablePropertiesToCategory($category, $contextObj, $categoryField, $primaryCategory, $skipCache);

        return $category->row();
    }

    /**
     * Get the category for a given category id.
     *
     * @param string|array $ids
     *
     * @return array|null
     */
    public function getCategories($ids)
    {
        $ids = StringUtil::deserialize($ids, true);

        if (empty($ids)) {
            return [];
        }

        $categories = System::getContainer()->get('huh.categories.manager')->findMultipleByIds($ids);

        if (null === $categories) {
            return null;
        }

        return $categories->fetchAll();
    }
}
