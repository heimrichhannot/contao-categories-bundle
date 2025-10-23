<?php

namespace HeimrichHannot\CategoriesBundle\Model;

use Contao\Model;
use Terminal42\DcMultilingualBundle\Model\MultilingualTrait;

if (trait_exists(MultilingualTrait::class)) {
    abstract class AbstractModel extends Model
    {
        use MultilingualTrait;
    }
} else {
    abstract class AbstractModel extends Model
    {
    }
}