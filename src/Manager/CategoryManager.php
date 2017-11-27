<?php

/*
 * Copyright (c) 2017 Heimrich & Hannot GmbH
 *
 * @license LGPL-3.0+
 */

namespace HeimrichHannot\CategoriesBundle\Manager;

use Contao\CoreBundle\Framework\ContaoFrameworkInterface;
use Contao\StringUtil;
use HeimrichHannot\CategoriesBundle\Backend\CategorySource;
use HeimrichHannot\CategoriesBundle\Model\CategoryAssociationModel;
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

    public function findByEntityAndField($entityId, $field)
    {
        /** @var CategoryAssociationModel $adapter */
        $adapter = $this->framework->getAdapter(CategoryAssociationModel::class);

        if (null === ($categoryAssociations = $adapter->findBy(['tl_category_association.field=?', 'tl_category_association.entity=?'], [$field, $entityId]))) {
            return null;
        }

        /** @var CategoryModel $adapter */
        $adapter = $this->framework->getAdapter(CategoryModel::class);

        return $adapter->findMultipleByIds($categoryAssociations->fetchEach('category'));
    }

    public function findOneByEntityAndField($entityId, $field)
    {
        /** @var CategoryAssociationModel $adapter */
        $adapter = $this->framework->getAdapter(CategoryAssociationModel::class);

        if (null === ($categoryAssociations = $adapter->findOneBy(['tl_category_association.entity=?', 'tl_category_association.field=?'], [$entityId, $field]))) {
            return null;
        }

        /** @var CategoryModel $adapter */
        $adapter = $this->framework->getAdapter(CategoryModel::class);

        return $adapter->findByPk($categoryAssociations->category);
    }

    public function getContextualProperty($context, $categoryField, $primaryCategoryId, $property)
    {
        $categoryFieldSourceMapping = StringUtil::deserialize(
            $context->{CategorySource::CATEGORY_FIELD_SOURCE_MAPPING_FIELD}, true);

        if (empty($categoryFieldSourceMapping)) {
            /** @var CategoryModel $adapter */
            $adapter = $this->framework->getAdapter(CategoryModel::class);

            if (null !== ($category = $adapter->findByPk($primaryCategoryId))) {
                return $category->{$property};
            }
        } else {
            $source = null;

            foreach ($categoryFieldSourceMapping as $mapping) {
                if (isset($mapping['field']) && $mapping['field'] === $categoryField) {
                    $source = $mapping['source'];
                    break;
                }
            }

            if (null !== $source) {
                /** @var CategoryConfigModel $adapter */
                $adapter = $this->framework->getAdapter(CategoryConfigModel::class);

                if (null === ($categoryConfig = $adapter->findOneBy(['tl_category_config.pid=?', 'tl_category_config.source=?'], [$primaryCategoryId, $source]))) {
                    return null;
                }

                return General::getOverridableProperty($property, [['tl_category', $primaryCategoryId], $categoryConfig]);
            }
        }

        return null;
    }

    public function createAssociations($entityId, $field, array $categories)
    {
        /** @var CategoryAssociationModel $adapter */
        $adapter = $this->framework->getAdapter(CategoryAssociationModel::class);

        // clean up beforehands
        if (null !== ($categoryAssociations = $adapter->findBy(['tl_category_association.entity=?', 'tl_category_association.field=?'], [$entityId, $field]))) {
            while ($categoryAssociations->next()) {
                $categoryAssociations->delete();
            }
        }

        foreach ($categories as $category) {
            $association = $this->framework->createInstance(CategoryAssociationModel::class);
            $association->tstamp = time();
            $association->category = $category;
            $association->entity = $entityId;
            $association->field = $field;
            $association->save();
        }
    }
}
