<?php

/*
 * Copyright (c) 2017 Heimrich & Hannot GmbH
 *
 * @license LGPL-3.0+
 */

namespace HeimrichHannot\CategoriesBundle\Manager;

use Contao\CoreBundle\Framework\ContaoFrameworkInterface;
use Contao\StringUtil;
use HeimrichHannot\CategoriesBundle\Backend\CategoryContext;
use HeimrichHannot\CategoriesBundle\Model\CategoryAssociationModel;
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

    /**
     * @param int    $entityId
     * @param string $field
     *
     * @return \Contao\Model\Collection|null
     */
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

    /**
     * @param int    $entityId
     * @param string $field
     *
     * @return null|CategoryModel
     */
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

    /**
     * Retrieves a property value based on a given context situation.
     *
     * These values can be defined in the following objects (lower number is lower priority):
     *
     * - in one of the parent categories of the category with id $primaryCategoryId or in a category config linked with the respective category
     *   (nested categories and their category configs have higher priority than their children categories and configs)
     * - in the category with id $primaryCategoryId
     * - in a category config linked with the category with id $primaryCategoryId
     *
     * Hint: The category config is chosen based on the context value defined in $contextObj for the the field $categoryField
     *
     * -> see README.md for further info
     *
     * @param string $property          The property defined in the primary category or an associated category config
     * @param object $contextObj        The context object containing the field-context-mapping for deciding which category config is taken into account
     * @param string $categoryField     The field containing the category (categories)
     * @param int    $primaryCategoryId The id of the primary category
     *
     * @return mixed|null
     */
    public function getOverridableProperty($property, $contextObj, $categoryField, $primaryCategoryId)
    {
        $categoryConfigManager = \System::getContainer()->get('huh.categories.config_manager');
        $relevantEntities = [];

        // compute context
        $context = null;

        $categoryFieldContextMapping = StringUtil::deserialize(
            $contextObj->{CategoryContext::CATEGORY_FIELD_CONTEXT_MAPPING_FIELD}, true);

        if (!empty($categoryFieldContextMapping)) {
            foreach ($categoryFieldContextMapping as $mapping) {
                if (isset($mapping['field']) && $mapping['field'] === $categoryField) {
                    $context = $mapping['context'];
                    break;
                }
            }
        }

        // parent categories
        $parentCategories = $this->getParentCategories($primaryCategoryId);

        if (!empty($parentCategories)) {
            foreach (array_reverse($parentCategories) as $parentCategory) {
                $relevantEntities[] = $parentCategory;

                if (null !== $context) {
                    if (null !== ($categoryConfig = $categoryConfigManager->findByCategoryAndContext($parentCategory->id, $context))) {
                        $relevantEntities[] = $categoryConfig;
                    }
                }
            }
        }

        // primary category
        $relevantEntities[] = ['tl_category', $primaryCategoryId];

        // category configs
        if (null !== $context) {
            if (null !== ($categoryConfig = $categoryConfigManager->findByCategoryAndContext($primaryCategoryId, $context))) {
                $relevantEntities[] = $categoryConfig;
            }
        }

        return General::getOverridableProperty($property, $relevantEntities);
    }

    /**
     * Adapter function for the model's findBy method.
     *
     * @param mixed $column
     * @param mixed $value
     * @param array $options
     *
     * @return \Contao\Model\Collection|CategoryModel|null
     */
    public function findBy($column, $value, array $options = [])
    {
        /** @var CategoryModel $adapter */
        $adapter = $this->framework->getAdapter(CategoryModel::class);

        return $adapter->findBy($column, $value, $options);
    }

    /**
     * Returns the parent categories of the category with the id $categoryId.
     * The order is from closest parent to root parent category.
     *
     * @param int $categoryId
     *
     * @return array
     */
    public function getParentCategories($categoryId, $insertCurrent = false)
    {
        $categories = [];

        if (null === ($category = $this->findBy('id', $categoryId))) {
            return [];
        }

        if (!$category->pid) {
            return [$category];
        }

        if ($insertCurrent) {
            $categories[] = $category;
        }

        $categories = array_merge($categories, $this->getParentCategories($category->pid, true));

        return $categories;
    }

    /**
     * Returns the parent categories' ids of the category with the id $categoryId.
     * The order is from closest parent to root parent category.
     *
     * @param int $categoryId
     *
     * @return array
     */
    public function getParentCategoryIds($categoryId)
    {
        $categories = [];

        if (null === ($category = $this->findBy('id', $categoryId))) {
            return [];
        }

        if (!$category->pid) {
            return [$categoryId];
        }

        $categories = array_merge($categories, $this->getParentCategories($category->pid));

        return $categories;
    }

    /**
     * Creates the association rows between entities and categories.
     *
     * @param int    $entityId
     * @param string $field
     * @param array  $categories
     */
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
