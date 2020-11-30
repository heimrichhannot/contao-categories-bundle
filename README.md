# Contao Categories Bundle

[![](https://img.shields.io/packagist/v/heimrichhannot/contao-categories-bundle.svg)](https://packagist.org/packages/heimrichhannot/contao-categories-bundle)
[![](https://img.shields.io/packagist/dt/heimrichhannot/contao-categories-bundle.svg)](https://packagist.org/packages/heimrichhannot/contao-categories-bundle)

This bundle allows to assign nestable categories to arbitrary entities in Contao-driven systems.

## Features

- the module is done in a generic way, i.e. you can assign category/categories field(s) to arbitrary DCAs
- conveniently add single category fields (radio button) or multiple categories fields (checkbox) via a simple function call
- specify overridable properties in categories and compute the correct result depending on the given context easily
- multilanguage support via [heimrichhannot/contao-categories-multilingual-bundle](https://github.com/heimrichhannot/contao-categories-multilingual-bundle)
- categories filter type for [Contao Filter Bundle](https://github.com/heimrichhannot/contao-filter-bundle)

## Impressions

### Category management

![alt preview](docs/categories.png)

Main category management view. By clicking the cog icon you can navigate to the *category configs*.

### Widget integration in your DCA

![alt preview](docs/fields.png)

Add category fields to your DCA easily. The category marked as *primary category* is colored in green.

### Picker widgets

![alt preview](docs/radio-picker.png)

Single category picker with radio buttons (selecting parent categories is allowed -> can be disallowed if necessary; no primary category marker necessary)

![alt preview](docs/checkbox-picker.png)

Single category picker with checkboxes (selecting parent categories is disallowed; the yellow asterisk marks the primary category -> this attribute is stored to an automatically created field named `<categoriesFieldname>_primary`)

## Usage

### Install

1. Install bundle with composer or contao manager

   ```
   composer require heimrichhannot/contao-categories-bundle
   ```
   
1. Update database

1. Add category support to the datacontainer you want, [e.g. news](docs/guide_news.md)

### Filter bundle integration

This bundle comes with two filter types:
- CategoryChoiceType let you select categories to filter a list. 
- ParentCategoryChoiceType is an inital filter to filter your list based on a parent category (means all elements are in a child category of the selected parent).

## Entity structure

![alt entities](docs/entities.png)

Table | Description
----- | -----------
tl_category | Contains the *category* instances
tl_category_association | Association table between tl_category and your DCA's table
tl_category_context | Defines context keys (simple strings) usable in *category configs*
tl_category_config | Contains *category configs*. Here you can override properties defined per default in a category linked with a certain *category context*
tl_category_property_cache | Contains the resolved overridable property values

## Documentation

[Concepts](docs/concepts.md)

[Technical instructions](docs/technical_intructions.md)

[Guide: News categories field](docs/guide_news.md)
