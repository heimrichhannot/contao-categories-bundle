<?php

/*
 * Copyright (c) 2021 Heimrich & Hannot GmbH
 *
 * @license LGPL-3.0-or-later
 */

namespace HeimrichHannot\CategoriesBundle\Model;

use Contao\Database;
use Contao\Model;
use Contao\Model\Collection;

/**
 * Class CategoryModel.
 *
 * @property int         $id;
 * @property int         $pid;
 * @property int         $sorting;
 * @property int         $tstamp;
 * @property int         $dateAdded;
 * @property string      $title;
 * @property string      $frontendTitle;
 * @property string      $alias;
 * @property string      $cssClass;
 * @property string      $jumpTo;
 * @property string|bool $selectable;
 */
class CategoryModel extends Model
{
    protected static $strTable = 'tl_category';

    /**
     * @return CategoryModel|CategoryModel[]|Collection|null
     */
    public function getChildCategories()
    {
        $result = Database::getInstance()->prepare('SELECT * FROM '.static::$strTable.' WHERE pid=?')->execute($this->id);

        if ($result->count() < 1) {
            return null;
        }
        $collection = Collection::createFromDbResult($result, static::$strTable);

        return $collection;
    }

    public function hasChildCategories(): bool
    {
        return null !== $this->getChildCategories();
    }

    /**
     * Return an one-dimensional list of all descendents of the current category.
     *
     * @return Collection|CategoryModel[]|CategoryModel|null $descendants
     */
    public function getDescendantCategories(array &$descendants = [])
    {
        $childs = $this->getChildCategories();

        if (!$childs) {
            return null;
        }

        foreach ($childs as $child) {
            $descendants[] = $child;
            $child->getDescendantCategories($descendants);
        }

        return new Collection($descendants, static::$strTable);
    }
}
