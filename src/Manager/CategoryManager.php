<?php

/*
 * Copyright (c) 2017 Heimrich & Hannot GmbH
 *
 * @license LGPL-3.0+
 */

namespace HeimrichHannot\CategoriesBundle\Manager;

use Contao\CoreBundle\Framework\ContaoFrameworkInterface;
use Contao\Model;
use Contao\Model\Collection;
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
     * @param int    $entity
     * @param string $categoryField
     * @param array  $options
     *
     * @return \Contao\Model\Collection|null
     */
    public function findByEntityAndCategoryField(int $entity, string $categoryField, array $options = [])
    {
        /** @var CategoryAssociationModel $adapter */
        $adapter = $this->framework->getAdapter(CategoryAssociationModel::class);

        if (null === ($categoryAssociations = $adapter->findBy(['tl_category_association.categoryField=?', 'tl_category_association.entity=?'], [$categoryField, $entity], $options))) {
            return null;
        }

        /** @var CategoryModel $adapter */
        $adapter = $this->framework->getAdapter(CategoryModel::class);

        return $adapter->findMultipleByIds($categoryAssociations->fetchEach('category'), [
            'order' => 'sorting ASC',
        ]);
    }

    /**
     * @param int    $entity
     * @param string $categoryField
     * @param array  $options
     *
     * @return null|CategoryModel
     */
    public function findOneByEntityAndCategoryField(int $entity, string $categoryField, array $options = []): ?CategoryModel
    {
        /** @var CategoryAssociationModel $adapter */
        $adapter = $this->framework->getAdapter(CategoryAssociationModel::class);

        if (null === ($categoryAssociations = $adapter->findOneBy(['tl_category_association.entity=?', 'tl_category_association.categoryField=?'], [$entity, $categoryField], $options))) {
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
     * - in one of the parent categories of the category with id $primaryCategory or in a category config linked with the respective category
     *   (nested categories and their category configs have higher priority than their children categories and configs)
     * - in the category with id $primaryCategory
     * - in a category config linked with the category with id $primaryCategory
     *
     * Hint: The category config is chosen based on the context value defined in $contextObj for the the field $categoryField
     *
     * -> see README.md for further info
     *
     * @param string $property        The property defined in the primary category or an associated category config
     * @param object $contextObj      The context object containing the field-context-mapping for deciding which category config is taken into account
     * @param string $categoryField   The field containing the category (categories)
     * @param int    $primaryCategory The id of the primary category
     * @param bool   $skipCache       Skip caching
     *
     * @return mixed|null
     */
    public function getOverridableProperty(string $property, $contextObj, string $categoryField, int $primaryCategory, bool $skipCache = false)
    {
        $categoryConfigManager = \System::getContainer()->get('huh.categories.config_manager');
        $relevantEntities = [];

        // compute context
        $context = $this->computeContext($contextObj, $categoryField);
        $cacheManager = \System::getContainer()->get('huh.categories.property_cache_manager');

        if (!$skipCache && null !== $context && $cacheManager->has($property, $categoryField, $primaryCategory, $context)) {
            return $cacheManager->get($property, $categoryField, $primaryCategory, $context);
        }

        // parent categories
        $parentCategories = $this->getParentCategories($primaryCategory);

        if (null !== $parentCategories) {
            foreach (array_reverse($parentCategories->getModels()) as $parentCategory) {
                $relevantEntities[] = $parentCategory;

                if (null !== $context) {
                    if (null !== ($categoryConfig = $categoryConfigManager->findByCategoryAndContext($parentCategory->id, $context))) {
                        $relevantEntities[] = $categoryConfig;
                    }
                }
            }
        }

        // primary category
        $relevantEntities[] = ['tl_category', $primaryCategory];

        // category configs
        if (null !== $context) {
            if (null !== ($categoryConfig = $categoryConfigManager->findByCategoryAndContext($primaryCategory, $context))) {
                $relevantEntities[] = $categoryConfig;
            }
        }

        $value = General::getOverridableProperty($property, $relevantEntities);

        if (!$skipCache && null !== $context) {
            $cacheManager->add($property, $categoryField, $primaryCategory, $context, $value);
        }

        return $value;
    }

    /**
     * Computes the context string for a given field based on a context object.
     *
     * @param $contextObj
     * @param string $categoryField
     *
     * @return null|int
     */
    public function computeContext($contextObj, string $categoryField): ?int
    {
        $categoryFieldContextMapping = StringUtil::deserialize(
            $contextObj->{CategoryContext::CATEGORY_FIELD_CONTEXT_MAPPING_FIELD}, true);

        if (!empty($categoryFieldContextMapping)) {
            foreach ($categoryFieldContextMapping as $mapping) {
                if (isset($mapping['categoryField']) && $mapping['categoryField'] === $categoryField) {
                    return (int) $mapping['context'];
                }
            }
        }

        return null;
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
     * Adapter function for the model's findBy method.
     *
     * @param mixed $column
     * @param mixed $value
     * @param array $options
     *
     * @return CategoryModel|null
     */
    public function findOneBy($column, $value, array $options = [])
    {
        /** @var CategoryModel $adapter */
        $adapter = $this->framework->getAdapter(CategoryModel::class);

        return $adapter->findOneBy($column, $value, $options);
    }

    /**
     * Returns the parent categories of the category with the id $categoryId.
     * The order is from closest parent to root parent category.
     *
     * @param int  $category
     * @param bool $insertCurrent
     *
     * @return Collection
     */
    public function getParentCategories(int $category, bool $insertCurrent = false): ?Collection
    {
        $categories = [];

        if (null === ($categoryObj = $this->findBy('id', $category))) {
            return null;
        }

        if (!$categoryObj->pid) {
            return new \Contao\Model\Collection([$categoryObj], 'tl_category');
        }

        if ($insertCurrent) {
            $categories[] = $categoryObj;
        }

        $parentCategories = $this->getParentCategories($categoryObj->pid, true);

        if (null !== $parentCategories) {
            $categories = array_merge($categories, $parentCategories->getModels());
        }

        return new \Contao\Model\Collection($categories, 'tl_category');
    }

    /**
     * Returns the parent categories' ids of the category with the id $categoryId.
     * The order is from closest parent to root parent category.
     *
     * @param int  $category
     * @param bool $insertCurrent
     *
     * @return array
     */
    public function getParentCategoryIds(int $category, bool $insertCurrent = false): array
    {
        $categories = [];

        if (null === ($categoryObj = $this->findBy('id', $category))) {
            return [];
        }

        if (!$categoryObj->pid) {
            return [$category];
        }

        if ($insertCurrent) {
            $categories[] = $category;
        }

        $categories = array_merge($categories, $this->getParentCategoryIds($categoryObj->pid, true));

        return $categories;
    }

    /**
     * Creates the association rows between entities and categories.
     *
     * @param int    $entity
     * @param string $categoryField
     * @param array  $categories
     */
    public function createAssociations(int $entity, string $categoryField, array $categories): void
    {
        /** @var CategoryAssociationModel $adapter */
        $adapter = $this->framework->getAdapter(CategoryAssociationModel::class);

        // clean up beforehands
        if (null !== ($categoryAssociations = $adapter->findBy(['tl_category_association.entity=?', 'tl_category_association.categoryField=?'], [$entity, $categoryField]))) {
            while ($categoryAssociations->next()) {
                $categoryAssociations->delete();
            }
        }

        foreach ($categories as $category) {
            $association = $this->framework->createInstance(CategoryAssociationModel::class);
            $association->tstamp = time();
            $association->category = $category;
            $association->entity = $entity;
            $association->categoryField = $categoryField;
            $association->save();
        }
    }

    /**
     * Determines whether a category has children.
     *
     * @param int $category
     *
     * @return bool
     */
    public function hasChildren(int $category): bool
    {
        /** @var CategoryModel $adapter */
        $adapter = $this->framework->getAdapter(CategoryModel::class);

        return null !== ($categoryAssociations = $adapter->findBy(['tl_category.pid=?'], [$category]));
    }
}
