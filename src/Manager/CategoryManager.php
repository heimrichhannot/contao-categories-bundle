<?php

/*
 * Copyright (c) 2017 Heimrich & Hannot GmbH
 *
 * @license LGPL-3.0+
 */

namespace HeimrichHannot\CategoriesBundle\Manager;

use Contao\Controller;
use Contao\CoreBundle\Framework\ContaoFrameworkInterface;
use Contao\Model\Collection;
use Contao\StringUtil;
use Contao\System;
use HeimrichHannot\CategoriesBundle\Backend\CategoryContext;
use HeimrichHannot\CategoriesBundle\Model\CategoryAssociationModel;
use HeimrichHannot\CategoriesBundle\Model\CategoryModel;

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
     * @param int $entity
     * @param string $categoryField
     * @param array $options
     *
     * @return \Contao\Model\Collection|null
     */
    public function findByEntityAndCategoryFieldAndTable(int $entity, string $categoryField, string $table, array $options = [])
    {
        $modelUtil = System::getContainer()->get('huh.utils.model');

        if (null === ($categoryAssociations = $modelUtil->findModelInstancesBy('tl_category_association', ['tl_category_association.categoryField=?', 'tl_category_association.entity=?', 'tl_category_association.parentTable=?'], [$categoryField, $entity, $table], $options))) {
            return null;
        }

        return $modelUtil->findMultipleModelInstancesByIds('tl_category', $categoryAssociations->fetchEach('category'), [
            'order' => 'sorting ASC',
        ]);
    }

    /**
     * @param string $categoryField
     * @param array $options
     *
     * @return \Contao\Model\Collection|null
     */
    public function findByCategoryFieldAndTable(string $categoryField, string $table, array $options = [])
    {
        $modelUtil = System::getContainer()->get('huh.utils.model');

        if (null === ($categoryAssociations = $modelUtil->findModelInstancesBy('tl_category_association', ['tl_category_association.categoryField=?', 'tl_category_association.parentTable=?'], [$categoryField, $table], $options))) {
            return null;
        }

        $options = [
            'order' => 'sorting ASC',
        ];

        return $modelUtil->findMultipleModelInstancesByIds('tl_category', $categoryAssociations->fetchEach('category'), $options);
    }

    /**
     * Get categories by dca field in table filtered by pids
     *
     * @param string $categoryField
     * @param string $table
     * @param array $pids Ids of parent categories
     * @param array $options
     *
     * @return \Contao\Model\Collection|null
     */
    public function findByCategoryFieldAndTableAndPids(string $categoryField, string $table, array $pids = [], array $options = [])
    {
        $modelUtil = System::getContainer()->get('huh.utils.model');

        if (null === ($categoryAssociations = $modelUtil->findModelInstancesBy('tl_category_association', ['tl_category_association.categoryField=?', 'tl_category_association.parentTable=?'], [$categoryField, $table], $options))) {
            return null;
        }

        $options = [
            'order' => 'sorting ASC',
        ];

        $ids     = $categoryAssociations->fetchEach('category');
        $columns = ["tl_category.id IN(" . implode(',', array_map('\intval', $ids)) . ')'];
        if (!empty($pids)) {
            $columns[] = 'tl_category.pid IN(' . implode(',', array_map('\intval', $pids)) . ')';
        }

        return $modelUtil->findModelInstancesBy(
            'tl_category',
            $columns,
            [],
            $options
        );
    }

    /**
     * @param int $entity
     * @param string $categoryField
     * @param array $options
     *
     * @return null|CategoryModel
     */
    public function findOneByEntityAndCategoryFieldAndTable(int $entity, string $categoryField, string $table, array $options = []): ?CategoryModel
    {
        $modelUtil = System::getContainer()->get('huh.utils.model');

        if (null === ($categoryAssociations = $modelUtil->findOneModelInstanceBy('tl_category_association', ['tl_category_association.entity=?', 'tl_category_association.categoryField=?', 'tl_category_association.parentTable=?'], [$entity, $categoryField, $table], $options))) {
            return null;
        }

        return $modelUtil->findModelInstanceByPk('tl_category', $categoryAssociations->category);
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
     * @param string $property The property defined in the category
     * @param int $category The id of the category
     *
     * @return mixed|null
     */
    public function getOverridablePropertyWithoutContext(string $property, int $category)
    {
        $relevantEntities = [];

        // parent categories
        $parentCategories = $this->getParentCategories($category);

        if (null !== $parentCategories) {
            foreach (array_reverse($parentCategories->getModels()) as $parentCategory) {
                $relevantEntities[] = $parentCategory;
            }
        }

        // category
        $relevantEntities[] = ['tl_category', $category];

        return System::getContainer()->get('huh.utils.dca')->getOverridableProperty($property, $relevantEntities);
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
     * @param string $property The property defined in the primary category or an associated category config
     * @param object $contextObj The context object containing the field-context-mapping for deciding which category config is taken into account
     * @param string $categoryField The field containing the category (categories)
     * @param int $primaryCategory The id of the primary category
     * @param bool $skipCache Skip caching
     *
     * @return mixed|null
     */
    public function getOverridableProperty(string $property, $contextObj, string $categoryField, int $primaryCategory, bool $skipCache = false)
    {
        $categoryConfigManager = System::getContainer()->get('huh.categories.config_manager');
        $relevantEntities      = [];

        // compute context
        $context      = $this->computeContext($contextObj, $categoryField);
        $cacheManager = System::getContainer()->get('huh.categories.property_cache_manager');

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

        $value = System::getContainer()->get('huh.utils.dca')->getOverridableProperty($property, $relevantEntities);

        if (!$skipCache && null !== $context) {
            $cacheManager->add($property, $categoryField, $primaryCategory, $context, $value);
        }

        return $value;
    }

    /**
     * Computes the context string for a given field based on a context object.
     *
     * @param        $contextObj
     * @param string $categoryField
     *
     * @return null|int
     */
    public function computeContext($contextObj, string $categoryField): ?int
    {
        $categoryFieldContextMapping = StringUtil::deserialize($contextObj->{CategoryContext::CATEGORY_FIELD_CONTEXT_MAPPING_FIELD}, true);

        if (!empty($categoryFieldContextMapping)) {
            foreach ($categoryFieldContextMapping as $mapping) {
                if (isset($mapping['categoryField']) && $mapping['categoryField'] === $categoryField) {
                    return (int)$mapping['context'];
                }
            }
        }

        return null;
    }

    /**
     * Adds all overridable property to $category.
     *
     * @param CategoryModel $category
     * @param $contextObj
     * @param string $categoryField
     * @param int $primaryCategory
     * @param bool $skipCache
     */
    public function addOverridablePropertiesToCategory(CategoryModel $category, $contextObj, string $categoryField, int $primaryCategory, bool $skipCache = false)
    {
        Controller::loadDataContainer('tl_category');

        foreach ($GLOBALS['TL_DCA']['tl_category']['fields'] as $field => $data) {
            if (isset($data['eval']['overridable']) && $data['eval']['overridable']) {
                $category->{$field} = $this->getOverridableProperty($field, $contextObj, $categoryField, $primaryCategory, $skipCache);
            }
        }
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
        return System::getContainer()->get('huh.utils.model')->findModelInstancesBy('tl_category', $column, $value, $options);
    }

    /**
     * Adapter function for the model's findAll method.
     *
     * @param array $options
     *
     * @return \Contao\Model\Collection|CategoryModel|null
     */
    public function findAll(array $options = [])
    {
        return System::getContainer()->get('huh.utils.model')->findAllModelInstances('tl_category', $options);
    }

    /**
     * Adapter function for the model's findMultipleByIds method.
     *
     * @param array $ids
     * @param array $options
     *
     * @return \Contao\Model\Collection|CategoryModel|null
     */
    public function findMultipleByIds(array $ids, array $options = [])
    {
        return System::getContainer()->get('huh.utils.model')->findMultipleModelInstancesByIds('tl_category', $ids, $options);
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
        return System::getContainer()->get('huh.utils.model')->findOneModelInstanceBy('tl_category', $column, $value, $options);
    }

    /**
     * Returns the parent categories of the category with the id $categoryId.
     * The order is from closest parent to root parent category.
     *
     * @param int $category
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
     * @param int $category
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
     * Removes all associations for a specific entity.
     *
     * @param int $entity
     * @param string $categoryField
     * @param string $table
     */
    public function removeAllAssociations(int $entity, string $categoryField, string $table): void
    {
        // clean up beforehand
        if (null !== ($categoryAssociations = System::getContainer()->get('huh.utils.model')->findModelInstancesBy('tl_category_association', ['tl_category_association.entity=?', 'tl_category_association.parentTable=?', 'tl_category_association.categoryField=?'], [$entity, $table, $categoryField]))) {
            while ($categoryAssociations->next()) {
                $categoryAssociations->delete();
            }
        }
    }

    /**
     * Creates the association rows between entities and categories.
     *
     * @param int $entity
     * @param string $categoryField
     * @param string $table
     * @param array $categories
     * @param bool $cleanBeforehand
     */
    public function createAssociations(int $entity, string $categoryField, string $table, array $categories, bool $cleanBeforehand = false): void
    {
        // clean up beforehand
        if ($cleanBeforehand)
        {
            $this->removeAllAssociations($entity, $categoryField, $table);
        }

        foreach ($categories as $category) {
            $association                = $this->framework->createInstance(CategoryAssociationModel::class);
            $association->tstamp        = time();
            $association->category      = $category;
            $association->entity        = $entity;
            $association->parentTable   = $table;
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
        return null !== System::getContainer()->get('huh.utils.model')->findModelInstancesBy('tl_category', ['tl_category.pid=?'], [$category]);
    }

    /**
     * @param string $parentTable
     * @param int $categoryId
     *
     * @return array
     */
    public function getAssociationsByParentTableAndCategory(int $categoryId, string $parentTable)
    {
        $result = [];

        $categoriesAssociations = $this->findAssociationsByParentTableAndCategory($categoryId, $parentTable);

        if (null === $categoriesAssociations) {
            return $result;
        }

        foreach ($categoriesAssociations as $categoriesAssociation) {
            $result[] = $categoriesAssociation->entity;
        }

        return $result;
    }

    public function findAssociationsByParentTableAndCategory(int $categoryId, string $parentTable)
    {
        return System::getContainer()->get('huh.utils.model')->findModelInstancesBy('tl_category_association', ['category=?', 'parentTable=?'], [$categoryId, $parentTable]);
    }

    public function findAssociationsByParentTableAndEntityAndField(string $parentTable, int $entityId, string $field)
    {
        return System::getContainer()->get('huh.utils.model')->findModelInstancesBy('tl_category_association', ['tl_category_association.parentTable=?', 'tl_category_association.entity=?', 'tl_category_association.categoryField=?'], [$parentTable, $entityId, $field]);
    }

    /**
     * find category by id or alias
     *
     * @param int $id
     * @param array $options
     *
     * @return CategoryModel|null
     */
    public function findByIdOrAlias($idOrAlias, array $options = [])
    {
        return System::getContainer()->get('huh.utils.model')->findModelInstanceByIdOrAlias('tl_category', $idOrAlias, $options);
    }

    /**
     * Find category and subcategory news categories by parent ID and IDs
     *
     * @param integer $intPid The parent ID
     * @param array $arrIds An array of categories
     *
     * @return \Model\Collection|CategoryModel[]|CategoryModel|null A collection of models or null if there are no categories
     */
    public function findCategoryAndSubcategoryByPidAndIds(int $pid, array $arrIds)
    {
        if (!is_array($arrIds) || empty($arrIds)) {
            return null;
        }

        $objCategories = \Database::getInstance()->prepare("SELECT c1.*, (SELECT COUNT(*) FROM tl_category  c2 WHERE c2.pid=c1.id AND c2.id IN (" . implode(',', array_map('intval', $arrIds)) . ")) AS subcategories FROM tl_category c1 WHERE c1.pid=? AND c1.id IN (" . implode(',', array_map('intval', $arrIds)) . ")")->execute($pid);

        if ($objCategories->numRows < 1) {
            return null;
        }

        return \Model\Collection::createFromDbResult($objCategories, 'tl_category');
    }

    /**
     * gets all entity ids by category and parent table
     *
     * @param string $category
     * @param string $parenTable
     *
     * @return array|null
     */
    public function getEntityIdsByCategoryAndParentTable(string $category, string $parenTable)
    {
        $objCategory = $this->findByIdOrAlias($category);

        if ($objCategory === null) {
            return null;
        }

        return $this->getAssociationsByParentTableAndCategory($objCategory->id, $parenTable);
    }
}
