<?php

/*
 * Copyright (c) 2020 Heimrich & Hannot GmbH
 *
 * @license LGPL-3.0-or-later
 */

namespace HeimrichHannot\CategoriesBundle\Model;

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
        return static::findBy([static::$strTable.'.pid=?'], [$this->id]);
    }

    public function hasChildCategories(): bool
    {
        return null !== $this->getChildCategories();
    }
}
