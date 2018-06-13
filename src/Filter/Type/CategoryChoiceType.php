<?php

/*
 * Copyright (c) 2018 Heimrich & Hannot GmbH
 *
 * @license LGPL-3.0-or-later
 */

namespace HeimrichHannot\CategoriesBundle\Filter\Type;

use Contao\System;
use HeimrichHannot\FilterBundle\Config\FilterConfig;
use HeimrichHannot\FilterBundle\Filter\Type\ChoiceType;
use HeimrichHannot\FilterBundle\Model\FilterConfigElementModel;

class CategoryChoiceType extends ChoiceType
{
    const TYPE = 'category_choice';

    protected $fieldOptions;

    public function __construct(FilterConfig $config)
    {
        parent::__construct($config);
        $this->fieldOptions = System::getContainer()->get('huh.categories.filter.choice');
    }

    public function getChoices(FilterConfigElementModel $element)
    {
        return $this->fieldOptions->getCachedChoices(['element' => $element, 'filter' => $this->config->getFilter()]);
    }
}
