<?php

/*
 * Copyright (c) 2021 Heimrich & Hannot GmbH
 *
 * @license LGPL-3.0-or-later
 */

namespace HeimrichHannot\CategoriesBundle\Manager;

use Contao\CoreBundle\Framework\ContaoFrameworkInterface;
use HeimrichHannot\CategoriesBundle\Model\CategoryPropertyCacheModel;

class CategoryPropertyCacheManager
{
    /**
     * @var ContaoFrameworkInterface
     */
    protected $framework;

    /**
     * Constructor.
     */
    public function __construct(ContaoFrameworkInterface $framework)
    {
        $this->framework = $framework;
    }

    /**
     * Adapter function for the model's findBy method.
     *
     * @param mixed $column
     * @param mixed $value
     *
     * @return \Contao\Model\Collection|CategoryPropertyCacheModel|null
     */
    public function findBy($column, $value, array $options = [])
    {
        /** @var CategoryPropertyCacheModel $adapter */
        $adapter = $this->framework->getAdapter(CategoryPropertyCacheModel::class);

        return $adapter->findBy($column, $value, $options);
    }

    /**
     * @param mixed $value
     */
    public function add(string $property, string $categoryField, int $category, int $context, $value): ?CategoryPropertyCacheModel
    {
        if (null !== ($item = $this->get($property, $categoryField, $category, $context))) {
            $item->value = $value;
            $item->save();
        } else {
            $item = $this->framework->createInstance(CategoryPropertyCacheModel::class);
            $item->tstamp = time();
            $item->property = $property;
            $item->categoryField = $categoryField;
            $item->category = $category;
            $item->context = $context;
            $item->value = $value;
            $item->save();
        }

        return $item;
    }

    /**
     * @return \Contao\Model\Collection|CategoryPropertyCacheModel|null
     */
    public function get(string $property, string $categoryField, int $category, int $context)
    {
        if (!$categoryField || !$category || !$context) {
            return null;
        }

        if (null !== ($item = $this->findBy(['property=?', 'categoryField=?', 'category=?', 'context=?'], [$property, $categoryField, $category, $context]))) {
            return $item->value;
        }

        return null;
    }

    public function has(string $property, string $categoryField, int $category, int $context): bool
    {
        if (!$property || !$categoryField || !$category || !$context) {
            return false;
        }

        return null !== $this->findBy(['property=?', 'categoryField=?', 'category=?', 'context=?'], [$property, $categoryField, $category, $context]);
    }

    /**
     * @param mixed $columns
     * @param mixed $values
     */
    public function delete($columns, $values): void
    {
        if (null !== ($items = $this->findBy($columns, $values))) {
            while ($items->next()) {
                $items->delete();
            }
        }
    }
}
