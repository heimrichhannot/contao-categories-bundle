<?php

$dca = &$GLOBALS['TL_DCA']['tl_news_feed'];

/**
 * Config
 */
$dca['config']['onload_callback']['generateFeed_huhCategories'] = [\HeimrichHannot\CategoriesBundle\DataContainer\NewsFeedContainer::class, 'generateFeed'];

/**
 * Palettes
 */
$dca['palettes']['default'] = str_replace('{config_legend', '{categories_legend},huhCategories,huhCategoriesFields,huhCategoriesShow;{config_legend', $dca['palettes']['default']);

/**
 * Fields
 */
$fields = [
    'huhCategoriesFields' => [
        'label'            => &$GLOBALS['TL_LANG']['tl_news_feed']['huhCategoriesFields'],
        'exclude'          => true,
        'filter'           => true,
        'inputType'        => 'select',
        'options_callback' => function (\Contao\DataContainer $dc) {
            return System::getContainer()->get('huh.utils.choice.field')->getCachedChoices([
                'dataContainer' => 'tl_news'
            ]);
        },
        'eval'             => ['tl_class' => 'long clr', 'includeBlankOption' => true, 'chosen' => true, 'multiple' => true],
        'sql'              => "blob NULL"
    ],
    'huhCategories'       => [
        'label'            => &$GLOBALS['TL_LANG']['tl_news_feed']['huhCategories'],
        'exclude'          => true,
        'filter'           => true,
        'inputType'        => 'checkbox',
        'options_callback' => function (\Contao\DataContainer $dc) {
            return System::getContainer()->get('huh.utils.choice.model_instance')->getCachedChoices([
                'dataContainer' => 'tl_category',
                'labelPattern'  => '%title% (ID %id%)'
            ]);
        },
        'eval'             => ['multiple' => true, 'tl_class' => 'long clr'],
        'sql'              => "blob NULL"
    ],
    'huhCategoriesShow'   => [
        'label'     => &$GLOBALS['TL_LANG']['tl_news_feed']['huhCategoriesShow'],
        'exclude'   => true,
        'filter'    => true,
        'inputType' => 'select',
        'options'   => ['title', 'text_before', 'text_after'],
        'reference' => &$GLOBALS['TL_LANG']['tl_news_feed']['huhCategoriesShow'],
        'eval'      => [
            'includeBlankOption' => true,
            'blankOptionLabel'   => $GLOBALS['TL_LANG']['tl_news_feed']['huhCategoriesShow']['empty'],
            'tl_class'           => 'long clr'
        ],
        'sql'       => "varchar(16) NOT NULL default ''"
    ]
];

$dca['fields'] = array_merge(is_array($dca['fields']) ? $dca['fields'] : [], $fields);
