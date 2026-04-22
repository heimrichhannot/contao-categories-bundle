<?php

namespace HeimrichHannot\CategoriesBundle\Flare\FilterElement;

use Contao\StringUtil;
use HeimrichHannot\CategoriesBundle\Model\CategoryModel;
use HeimrichHannot\FlareBundle\DependencyInjection\Attribute\AsFilterElement;
use HeimrichHannot\FlareBundle\Event\FilterElementFormTypeOptionsEvent;
use HeimrichHannot\FlareBundle\Filter\FilterInvocation;
use HeimrichHannot\FlareBundle\FilterElement\AbstractFilterElement;
use HeimrichHannot\FlareBundle\Query\FilterQueryBuilder;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;

#[AsFilterElement(
    type: self::TYPE,
    palette: '{fieldGeneric_legend},fieldGeneric,filterCategory;{form_legend},isMandatory,placeholder',
    formType: ChoiceType::class,
)]
class ParentCategoryFilterElement extends AbstractFilterElement
{
    public const TYPE = 'parent_category';

    public function __invoke(FilterInvocation $inv, FilterQueryBuilder $qb): void
    {
        if (!$value = (int) $inv->getValue()) {
            return;
        }

        if (!$targetField = $inv->filter->fieldGeneric) {
            $qb->abort();
        }

        $colField = $qb->column($targetField);

        $qb->where($qb->expr()->like($colField, ':category'))
            ->setParameter('category', '%"' . $value . '"%');
    }

    public function handleFormTypeOptions(FilterElementFormTypeOptionsEvent $event): void
    {
        $filter = $event->filter;

        $event->options = $this->defaultFormTypeOptions($event->filter, ['placeholder' => true]);
        $event->options['required'] = (bool) $filter->isMandatory;
        $event->options['choices'] = [];

        if (!$categoryIds = StringUtil::deserialize($filter->filterCategory, true)) {
            return;
        }

        if (!$categories = CategoryModel::findByPids($categoryIds, ['order' => 'title ASC'])) {
            return;
        }

        while ($categories->next()) {
            if ($title = $categories->title) {
                $event->options['choices'][$title] = (string) $categories->id;
            }
        }
    }
}