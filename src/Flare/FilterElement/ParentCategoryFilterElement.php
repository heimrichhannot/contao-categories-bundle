<?php

namespace HeimrichHannot\CategoriesBundle\Flare\FilterElement;

use Contao\StringUtil;
use HeimrichHannot\CategoriesBundle\Model\CategoryModel;
use HeimrichHannot\FlareBundle\DependencyInjection\Attribute\AsFilterElement;
use HeimrichHannot\FlareBundle\Filter\FilterContext;
use HeimrichHannot\FlareBundle\Filter\FilterQueryBuilder;
use HeimrichHannot\FlareBundle\FilterElement\AbstractFilterElement;
use HeimrichHannot\FlareBundle\Form\ChoicesBuilder;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;

#[AsFilterElement(
    alias: self::TYPE,
    palette: '{fieldGeneric_legend},fieldGeneric,filterCategory;{form_legend},placeholder',
    formType: ChoiceType::class,
)]
class ParentCategoryFilterElement extends AbstractFilterElement
{
    public const TYPE = 'parent_category';

    public function __invoke(FilterContext $context, FilterQueryBuilder $qb): void
    {
        if (!$context->getSubmittedData()) {
            return;
        }

        if (!$targetField = $context->getFilterModel()->fieldGeneric) {
            $qb->abort();
        }

        $qb->where($qb->expr()->like($targetField, ':category'))
            ->setParameter('category', '%"' . $context->getSubmittedData() . '"%');
    }

    public function getFormTypeOptions(FilterContext $context, ChoicesBuilder $choices): array
    {
        $options = $this->defaultFormTypeOptions(
            $context,
            ['placeholder' => true]
        );
        $options['choices'] = [];

        $categoryIds = StringUtil::deserialize($context->getFilterModel()->filterCategory, true);

        if (empty($categoryIds)) {
            return $options;
        }

        $categories = CategoryModel::findByPids(
            $categoryIds,
            ['order' => 'title ASC']
        );

        while ($categories->next()) {
            $options['choices'][$categories->title] = (string) $categories->id;
        }

        return $options;
    }
}