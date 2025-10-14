<?php

/*
 * Copyright (c) 2021 Heimrich & Hannot GmbH
 *
 * @license LGPL-3.0-or-later
 */

namespace HeimrichHannot\CategoriesBundle\Manager;

use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\Model\Collection;
use HeimrichHannot\CategoriesBundle\Model\CategoryContextModel;

class CategoryContextManager
{
    /**
     * @var ContaoFramework
     */
    protected $framework;

    /**
     * Constructor.
     */
    public function __construct(ContaoFramework $framework)
    {
        $this->framework = $framework;
    }

    /**
     * Adapter function for the model's findBy method.
     *
     * @param mixed $column
     * @param mixed $value
     *
     * @return Collection|CategoryContextModel|null
     */
    public function findBy($column, $value, array $options = [])
    {
        /** @var CategoryContextModel $adapter */
        $adapter = $this->framework->getAdapter(CategoryContextModel::class);

        return $adapter->findBy($column, $value, $options);
    }

    /**
     * Adapter function for the model's findBy method.
     *
     * @param mixed $column
     * @param mixed $value
     *
     * @return CategoryContextModel|null
     */
    public function findOneBy($column, $value, array $options = [])
    {
        /** @var CategoryContextModel $adapter */
        $adapter = $this->framework->getAdapter(CategoryContextModel::class);

        return $adapter->findOneBy($column, $value, $options);
    }
}
