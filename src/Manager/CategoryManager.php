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
     * @param int    $entityId
     * @param string $field
     * @param array  $options
     *
     * @return \Contao\Model\Collection|null
     */
    public function findByEntityAndField(int $entityId, string $field, array $options = [])
    {
        /** @var CategoryAssociationModel $adapter */
        $adapter = $this->framework->getAdapter(CategoryAssociationModel::class);

        if (null === ($categoryAssociations = $adapter->findBy(['tl_category_association.field=?', 'tl_category_association.entity=?'], [$field, $entityId], $options))) {
            return null;
        }

        /** @var CategoryModel $adapter */
        $adapter = $this->framework->getAdapter(CategoryModel::class);

        return $adapter->findMultipleByIds($categoryAssociations->fetchEach('category'), [
            'order' => 'sorting ASC',
        ]);
    }

    /**
     * @param int    $entityId
     * @param string $field
     * @param array  $options
     *
     * @return null|CategoryModel
     */
    public function findOneByEntityAndField(int $entityId, string $field, array $options = []): ?CategoryModel
    {
        /** @var CategoryAssociationModel $adapter */
        $adapter = $this->framework->getAdapter(CategoryAssociationModel::class);

        if (null === ($categoryAssociations = $adapter->findOneBy(['tl_category_association.entity=?', 'tl_category_association.field=?'], [$entityId, $field], $options))) {
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
     * @param bool   $skipCache         Skip caching
     *
     * @return mixed|null
     */
    public function getOverridableProperty(string $property, $contextObj, string $categoryField, int $primaryCategoryId, bool $skipCache = false)
    {
        $categoryConfigManager = \System::getContainer()->get('huh.categories.config_manager');
        $relevantEntities = [];

        // compute context
        $context = $this->computeContext($contextObj, $categoryField);
        $cacheManager = \System::getContainer()->get('huh.categories.property_cache_manager');

        if (!$skipCache && null !== $context && $cacheManager->has($property, $categoryField, $primaryCategoryId, $context)) {
            return $cacheManager->get($property, $categoryField, $primaryCategoryId, $context);
        }

        // parent categories
        $parentCategories = $this->getParentCategories($primaryCategoryId);

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
        $relevantEntities[] = ['tl_category', $primaryCategoryId];

        // category configs
        if (null !== $context) {
            if (null !== ($categoryConfig = $categoryConfigManager->findByCategoryAndContext($primaryCategoryId, $context))) {
                $relevantEntities[] = $categoryConfig;
            }
        }

        $value = General::getOverridableProperty($property, $relevantEntities);

        if (!$skipCache && null !== $context) {
            $cacheManager->add($property, $categoryField, $primaryCategoryId, $context, $value);
        }

        return $value;
    }

    /**
     * Computes the context string for a given field based on a context object.
     *
     * @param $contextObj
     * @param string $categoryField
     *
     * @return null|string
     */
    public function computeContext($contextObj, string $categoryField): ?string
    {
        $categoryFieldContextMapping = StringUtil::deserialize(
            $contextObj->{CategoryContext::CATEGORY_FIELD_CONTEXT_MAPPING_FIELD}, true);

        if (!empty($categoryFieldContextMapping)) {
            foreach ($categoryFieldContextMapping as $mapping) {
                if (isset($mapping['field']) && $mapping['field'] === $categoryField) {
                    return $mapping['context'];
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
     * @param int  $categoryId
     * @param bool $insertCurrent
     *
     * @return Collection
     */
    public function getParentCategories(int $categoryId, bool $insertCurrent = false): ?Collection
    {
        $categories = [];

        if (null === ($category = $this->findBy('id', $categoryId))) {
            return null;
        }

        if (!$category->pid) {
            return new \Contao\Model\Collection([$category], 'tl_category');
        }

        if ($insertCurrent) {
            $categories[] = $category;
        }

        $parentCategories = $this->getParentCategories($category->pid, true);

        if (null !== $parentCategories) {
            $categories = array_merge($categories, $parentCategories->getModels());
        }

        return new \Contao\Model\Collection($categories, 'tl_category');
    }

    /**
     * Returns the parent categories' ids of the category with the id $categoryId.
     * The order is from closest parent to root parent category.
     *
     * @param int  $categoryId
     * @param bool $insertCurrent
     *
     * @return array
     */
    public function getParentCategoryIds(int $categoryId, bool $insertCurrent = false): array
    {
        $categories = [];

        if (null === ($category = $this->findBy('id', $categoryId))) {
            return [];
        }

        if (!$category->pid) {
            return [$categoryId];
        }

        if ($insertCurrent) {
            $categories[] = $categoryId;
        }

        $categories = array_merge($categories, $this->getParentCategoryIds($category->pid, true));

        return $categories;
    }

    /**
     * Creates the association rows between entities and categories.
     *
     * @param int    $entityId
     * @param string $field
     * @param array  $categories
     */
    public function createAssociations(int $entityId, string $field, array $categories): void
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

    /**
     * Determines whether a category has children.
     *
     * @param int $categoryId
     *
     * @return bool
     */
    public function hasChildren(int $categoryId): bool
    {
        /** @var CategoryModel $adapter */
        $adapter = $this->framework->getAdapter(CategoryModel::class);

        return null !== ($categoryAssociations = $adapter->findBy(['tl_category.pid=?'], [$categoryId]));
    }
}
