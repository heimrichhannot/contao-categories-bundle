<?php

/*
 * Copyright (c) 2018 Heimrich & Hannot GmbH
 *
 * @license LGPL-3.0-or-later
 */

namespace HeimrichHannot\CategoriesBundle\Filter\Choice;

use Contao\CoreBundle\Framework\ContaoFrameworkInterface;
use Contao\StringUtil;
use HeimrichHannot\CategoriesBundle\Manager\CategoryManager;
use HeimrichHannot\FilterBundle\Choice\FieldOptionsChoice;
use HeimrichHannot\FilterBundle\Model\FilterConfigElementModel;

class CategoriesChoice extends FieldOptionsChoice
{
    /**
     * @var CategoryManager
     */
    private $categoryManager;

    public function __construct(ContaoFrameworkInterface $framework, CategoryManager $categoryManager)
    {
        parent::__construct($framework);
        $this->categoryManager = $categoryManager;
    }

    /**
     * Get category widget options.
     *
     * @param FilterConfigElementModel $element
     * @param array                    $filter
     * @param array                    $dca
     *
     * @return array
     */
    protected function getCategoryWidgetOptions(FilterConfigElementModel $element, array $filter, array $dca)
    {
        $options = [];
        $parentCategories = StringUtil::deserialize($element->parentCategories, true);

        if (!$categories = $this->categoryManager->findByCategoryFieldAndTableAndPids(
            $element->field,
            $filter['dataContainer'],
            $parentCategories
        )
        ) {
            return $options;
        }

        /** @var \HeimrichHannot\CategoriesBundle\Model\CategoryModel $category */
        foreach ($categories as $category)
        {
            $options[] = ['label' => ($category->frontendTitle ?: $category->title) . ' (ID ' . $category->id . ')', 'value' => $category->id];
        }

        return $options;
    }
}
