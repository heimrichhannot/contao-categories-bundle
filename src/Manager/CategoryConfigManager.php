<?php

/*
 * Copyright (c) 2020 Heimrich & Hannot GmbH
 *
 * @license LGPL-3.0-or-later
 */

namespace HeimrichHannot\CategoriesBundle\Manager;

use Contao\CoreBundle\Framework\ContaoFrameworkInterface;
use HeimrichHannot\CategoriesBundle\Model\CategoryConfigModel;

class CategoryConfigManager
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
     * @return \Contao\Model\Collection|CategoryConfigModel|null
     */
    public function findBy($column, $value, array $options = [])
    {
        /** @var CategoryConfigModel $adapter */
        $adapter = $this->framework->getAdapter(CategoryConfigModel::class);

        return $adapter->findBy($column, $value, $options);
    }

    /**
     * @return CategoryConfigModel
     */
    public function findByCategoryAndContext(int $category, int $context): ?CategoryConfigModel
    {
        /** @var CategoryConfigModel $adapter */
        $adapter = $this->framework->getAdapter(CategoryConfigModel::class);

        return $categoryConfig = $adapter->findOneBy(['tl_category_config.pid=?', 'tl_category_config.context=?'], [$category, $context]);
    }

    /**
     * Adapter function for the model's findBy method.
     *
     * @param mixed $column
     * @param mixed $value
     *
     * @return CategoryConfigModel|null
     */
    public function findOneBy($column, $value, array $options = [])
    {
        /** @var CategoryConfigModel $adapter */
        $adapter = $this->framework->getAdapter(CategoryConfigModel::class);

        return $adapter->findOneBy($column, $value, $options);
    }
}
