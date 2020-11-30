<?php

/*
 * Copyright (c) 2020 Heimrich & Hannot GmbH
 *
 * @license LGPL-3.0-or-later
 */

namespace HeimrichHannot\CategoriesBundle\Filter\Type;

use Contao\Model\Collection;
use Contao\StringUtil;
use Contao\System;
use HeimrichHannot\CategoriesBundle\Model\CategoryModel;
use HeimrichHannot\FilterBundle\Filter\AbstractType;
use HeimrichHannot\FilterBundle\Model\FilterConfigElementModel;
use HeimrichHannot\FilterBundle\QueryBuilder\FilterQueryBuilder;
use HeimrichHannot\UtilsBundle\Database\DatabaseUtil;
use Symfony\Component\Form\FormBuilderInterface;

class ParentCategoryChoiceType extends AbstractType
{
    const TYPE = 'parent_category_choice';

    public function buildQuery(FilterQueryBuilder $builder, FilterConfigElementModel $element)
    {
        $parentCategorieIds = StringUtil::deserialize($element->parentCategories, true);
        /** @var CategoryModel[]|Collection|null $parentCategories */
        $parentCategories = System::getContainer()->get('huh.utils.model')->findMultipleModelInstancesByIds('tl_category', $parentCategorieIds);

        if (!$parentCategories) {
            return;
        }
        $childCategories = [];

        foreach ($parentCategories as $category) {
            $descendants = $category->getDescendantCategories();

            if (!$descendants) {
                continue;
            }
            $childCategories = array_merge($childCategories, $descendants->getModels());
        }
        $childCategoryIds = (new Collection($childCategories, 'tl_category'))->fetchEach('id');

        $table = $this->config->getFilter()['dataContainer'];
        $field = &$GLOBALS['TL_DCA'][$table]['fields'][$element->field];

        if (isset($field['eval']['multiple']) && true === $field['eval']['multiple']) {
            $whereCondition = System::getContainer()->get('huh.utils.database')
                ->createWhereForSerializedBlob($element->field, $childCategoryIds, DatabaseUtil::SQL_CONDITION_OR, ['inline_values' => true]);

            $builder->andWhere($whereCondition[0]);
        } else {
            $builder->andWhere(System::getContainer()->get('huh.utils.database')
                ->composeWhereForQueryBuilder($builder, $element->field, DatabaseUtil::OPERATOR_IN, $field, $childCategoryIds));
        }
    }

    public function buildForm(FilterConfigElementModel $element, FormBuilderInterface $builder)
    {
    }

    public function getDefaultOperator(FilterConfigElementModel $element)
    {
        return DatabaseUtil::OPERATOR_LIKE;
    }

    public function getDefaultName(FilterConfigElementModel $element)
    {
        return null;
    }
}
