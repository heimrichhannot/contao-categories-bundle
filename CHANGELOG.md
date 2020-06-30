# Changelog
All notable changes to this project will be documented in this file.

## [1.0.1] - 2020-06-30
- fixed request token issue for contao 4.9

## [1.0.0] - 2020-06-22
- added ondelete_callback in order to remove associations of a deleted category

## [1.0.0-beta33] - 2020-06-15
- fixed localization

## [1.0.0-beta32] - 2020-06-15
- fixed issues with news categories

## [1.0.0-beta31] - 2020-06-15
- added support for news feeds based on categories

## [1.0.0-beta30] - 2020-06-02
- fixed fe filter choice bug

## [1.0.0-beta29] - 2020-05-27
- deprecated `Category::getCategoryFieldDca()` in favor of `Category::addSingleCategoryFieldToDca()`
- added association deletion in case of the deletion of the associated entity (e.g. tl_news)

## [1.0.0-beta28] - 2020-05-27
- fixed choice option bug (label and value were swapped)

## [1.0.0-beta27] - 2019-11-07

- added migration command for contao-news_categories

## [1.0.0-beta26] - 2019-10-23

#### Added
- support for disabled/readonly state of a category/categories field

#### Added
- null check in twig filter

## [1.0.0-beta25] - 2019-07-10

#### Fixed
- filter issues
- user group legend issue

## [1.0.0-beta24] - 2019-04-04

#### Removed
- haste leftover

#### Fixed
- some deprecation warnings
- some namespaces

## [1.0.0-beta23] - 2019-04-04

#### Fixed
- symfony 4 compatibility

## [1.0.0-beta22] - 2019-03-19

#### Changed
- version 2 of `heimrichhannot/contao-multi-column-editor-bundle` as dependency

## [1.0.0-beta21] - 2019-01-15

#### Fixed
- uncheck categories now properly works again 

## [1.0.0-beta20] - 2018-12-18

#### Changed
- category manager functions

## [1.0.0-beta19] - 2018-11-12

#### Fixed
- removal of categories already set wasn't possible

## [1.0.0-beta18] - 2018-09-03

#### Fixed
- Category::storeToCategoryAssociations() -> fix for radio fields

## [1.0.0-beta17] - 2018-09-03

#### Fixed
- Category::storeToCategoryAssociations() -> categories are now stored as strings in case of a multiple field so that contao backend list filtering works

## [1.0.0-beta16] - 2018-07-16

#### Fixed
- Category::storeToCategoryAssociations()

## [1.0.0-beta15] - 2018-06-14

#### Added 
- CategoryManager::getOverridablePropertyWithoutContext()

#### Fixed
- minor styling issues

## [1.0.0-beta14] - 2018-06-13

#### Added 
- categories filter for [Contao Filter Bundle](https://github.com/heimrichhannot/contao-filter-bundle)

#### Changed
- more dependency injection for hook listener

## [1.0.0-beta13] - 2018-06-12

#### Added
- support for heimrichhannot/contao-categories-multilingual-bundle

## [1.0.0-beta12] - 2018-06-07

#### Changed
- replaced `heimrichhannot/contao-haste_plus` with `heimrichhannot/contao-utils-bundle`
- removed `heimrichhannot/contao-multi_column_editor` with `heimrichhannot/contao-multi-column-editor-bundle`

## [1.0.0-beta11] - 2018-05-30

#### Added
- selectable option support
- documentation for rootNodes

## [1.0.0-beta10] - 2018-03-15

#### Fixed
- issue with overridable fields

## [1.0.0-beta9] - 2018-03-05

#### Added
- `tl_category` breadcrumb, to limit current tree view 

## [1.0.0-beta8] - 2018-02-15

#### Changed
- `tl_category.alias` prevent from being copied `doNotCopy` 

## [1.0.0-beta7] - 2018-02-15

#### Added
- `contextualCategory` twig filter

## [1.0.0-beta6] - 2018-02-15

#### Added
- `category` and `categories` twig filters

## [1.0.0-beta5] - 2018-02-14

#### Added
- `CategoryManager::findByCategoryFieldAndTable` 

## [1.0.0-beta4] - 2018-02-05

#### Fixed
- alias genaration for new categories

## [1.0.0-beta3] - 2018-01-24

### Added
- translation support for `title` and `frontendTitle` with symfony translation service

### Changed
- licence LGPL-3.0+ is now LGPL-3.0-or-later

## [1.0.0-beta2] - 2017-12-18

### Added
- added frontend module categoriesMenu and functions to filter a list by categories

## [1.0.0-beta] - 2017-12-06

### Added
- initial version
