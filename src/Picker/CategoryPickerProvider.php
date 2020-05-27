<?php

/*
 * Copyright (c) 2020 Heimrich & Hannot GmbH
 *
 * @license LGPL-3.0-or-later
 */

namespace HeimrichHannot\CategoriesBundle\Picker;

use Contao\CoreBundle\Picker\AbstractPickerProvider;
use Contao\CoreBundle\Picker\DcaPickerProviderInterface;
use Contao\CoreBundle\Picker\PickerConfig;
use Contao\System;

class CategoryPickerProvider extends AbstractPickerProvider implements DcaPickerProviderInterface
{
    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'categoryPicker';
    }

    /**
     * {@inheritdoc}
     */
    public function supportsContext($context)
    {
        return \in_array($context, ['category'], true) && $this->getUser()->hasAccess('categories', 'modules');
    }

    /**
     * {@inheritdoc}
     */
    public function supportsValue(PickerConfig $config)
    {
        if ('category' === $config->getContext()) {
            return is_numeric($config->getValue());
        }

        return false !== strpos($config->getValue(), '{{category_url::');
    }

    /**
     * {@inheritdoc}
     */
    public function getDcaTable()
    {
        return 'tl_category';
    }

    /**
     * {@inheritdoc}
     */
    public function getDcaAttributes(PickerConfig $config)
    {
        $value = $config->getValue();
        $attributes = ['fieldType' => 'radio'];

        if ('category' === $config->getContext()) {
            if ($fieldType = $config->getExtra('fieldType')) {
                $attributes['fieldType'] = $fieldType;
            }

            if ($source = $config->getExtra('source')) {
                $attributes['preserveRecord'] = $source;
            }

            if (\is_array($rootNodes = $config->getExtra('rootNodes'))) {
                $attributes['rootNodes'] = $rootNodes;
            }

            if ($value) {
                $attributes['value'] = array_map('intval', explode(',', $value));
            }

            return $attributes;
        }

        if ($value && false !== strpos($value, '{{category_url::')) {
            $attributes['value'] = str_replace(['{{category_url::', '}}'], '', $value);
        }

        return $attributes;
    }

    /**
     * {@inheritdoc}
     */
    public function convertDcaValue(PickerConfig $config, $value)
    {
        if ('category' === $config->getContext()) {
            return (int) $value;
        }

        return '{{category_url::'.$value.'}}';
    }

    /**
     * {@inheritdoc}
     */
    protected function getRouteParameters(PickerConfig $config = null)
    {
        return [
            'do' => 'categories',
            'category_field' => System::getContainer()->get('huh.request')->getGet('category_field'),
            'category_table' => System::getContainer()->get('huh.request')->getGet('category_table'),
            'primaryCategory' => System::getContainer()->get('huh.request')->getGet('primaryCategory'),
            'usePrimaryCategory' => System::getContainer()->get('huh.request')->getGet('usePrimaryCategory'),
        ];
    }
}
