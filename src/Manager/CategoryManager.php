<?php

/*
 * Copyright (c) 2017 Heimrich & Hannot GmbH
 *
 * @license LGPL-3.0+
 */

namespace HeimrichHannot\CategoriesBundle\Manager;

use Contao\CoreBundle\Framework\ContaoFrameworkInterface;
use HeimrichHannot\CategoriesBundle\Model\CategoryAssocModel;
use HeimrichHannot\CategoriesBundle\Model\CategoryConfigModel;
use HeimrichHannot\CategoriesBundle\Model\CategoryModel;
use HeimrichHannot\Haste\Dca\General;

class CategoryManager
{
    /**
     * @var ContaoFrameworkInterface
     */
    protected $framework;

    /**
     * Constructor.
     *
     * @param ContaoFrameworkInterface $framework
     */
    public function __construct(ContaoFrameworkInterface $framework)
    {
        $this->framework = $framework;
    }

    public function find($value, array $criteria = [])
    {
        /** @var CategoryModel $adapter */
        $adapter = $this->framework->getAdapter(CategoryModel::class);

        if (null === ($model = $adapter->findByPk($value))) {
            return null;
        }

        $criteria = $this->getCriteria($criteria);

        // Check the source
        if ($model->source !== $criteria['source']) {
            return null;
        }

        return ModelCollection::createTagFromModel($model);
    }

    public function findByEntityAndField($field, $entity)
    {
        /** @var CategoryAssocModel $adapter */
        $adapter = $this->framework->getAdapter(CategoryAssocModel::class);

        if (null === ($categoryAssoc = $adapter->findBy(['field=?', 'entity=?'], [$field, $entity]))) {
            return null;
        }

        /** @var CategoryModel $adapter */
        $adapter = $this->framework->getAdapter(CategoryModel::class);

        return $adapter->findMultipleByIds($categoryAssoc->fetchEach('category'));
    }

    public function getContextualProperty($source, $primaryCategory, $source, $property)
    {
        /** @var CategoryConfigModel $adapter */
        $adapter = $this->framework->getAdapter(CategoryConfigModel::class);

        if (null === ($categoryConfig = $adapter->findBy(['source=?', 'pid=?'], [$source, $primaryCategory]))) {
            return null;
        }

        return General::getOverridableProperty($property, [['tl_category', $primaryCategory], $categoryConfig]);
    }

    /**
     * Get the criteria with necessary data.
     *
     * @param array $criteria
     *
     * @return array
     */
    protected function getCriteria(array $criteria = [])
    {
        $criteria['source'] = $this->alias;
        $criteria['sourceTable'] = $this->sourceTable;
        $criteria['sourceField'] = $this->sourceField;

        return $criteria;
    }
}
