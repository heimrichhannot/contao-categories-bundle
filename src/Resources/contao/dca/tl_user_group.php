<?php

\System::loadLanguageFile('tl_user');

$GLOBALS['TL_DCA']['tl_user_group']['palettes']['default'] = str_replace('newsfeedp;', 'newsfeedp;{categories_legend},categories,categories_default;', $GLOBALS['TL_DCA']['tl_user_group']['palettes']['default']);

$GLOBALS['TL_DCA']['tl_user_group']['fields']['categories'] = [
    'label'     => &$GLOBALS['TL_LANG']['tl_user']['categories'],
    'exclude'   => true,
    'inputType' => 'checkbox',
    'options'   => ['manage'],
    'reference' => &$GLOBALS['TL_LANG']['tl_user']['categoriesRef'],
    'eval'      => ['multiple' => true, 'tl_class' => 'clr'],
    'sql'       => "varchar(32) NOT NULL default ''"
];

$GLOBALS['TL_DCA']['tl_user_group']['fields']['categories_default'] = [
    'label'      => &$GLOBALS['TL_LANG']['tl_user']['categories_default'],
    'exclude'    => true,
    'inputType'  => 'treePicker',
    'foreignKey' => 'tl_category.title',
    'eval'       => ['multiple' => true, 'fieldType' => 'checkbox', 'foreignTable' => 'tl_category', 'titleField' => 'title', 'searchField' => 'title', 'managerHref' => 'do=categories'],
    'sql'        => "blob NULL"
];
