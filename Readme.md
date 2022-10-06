
TYPO3 Extension: Pagetree Filter
================================

It is able to highlight pages with certain records or content elements in the pagetree. For simplicity it comes with a wizard.

## Examples

Highlight pages with...

* sys_category records: `table=sys_category`
* image content elements: `table=tt_content CType=image`
* hidden image content elements: `table=tt_content CType=image hidden=1`
* content elements where the elements header starts with rocket: `table=tt_content header=rocket*`
* content elements where the elements header ends with launch: `table=tt_content header=*launch`
* content elements where the elements header contains rocketlaunch: `table=tt_content header=*rocketlaunch*`

## Screenshots

### Highlight pages with image elements

![Example](https://raw.githubusercontent.com/christophlehmann/pagetreefilter/master/Documentation/Images/filter-example.png)

### The filter wizard

![The Wizard](https://raw.githubusercontent.com/christophlehmann/pagetreefilter/master/Documentation/Images/filter-wizard.png)

## Installation

`composer require christophlehmann/pagetreefilter`

## Configuration

### Enable for non admin users (User TSconfig)

`tx_pagetreefilter.enable = 1`

### PageId

The wizard needs a page id for fetching Page TSconfig in order to gather possible content elements and plugins. This page id is determined by

1. First WebMount in case the user is no admin
2. Extension configuration: `pageId` (default: `1`)

### ~~ignoreFields (Page TSconfig)~~

~~The wizard creates a filter based on~~ `tt_content_defValues`~~, see~~ `mod.wizards.newContentElement.wizardItems`.
~~If you don't like them, then you can ignore them:~~ `tx_pagetreefilter.buildFilter.ignoreFields = tx_gridelements_columns`

The settings is removed in v1.4.0. For now only `CType` and `list_type` are used to filter for content elements and to determine if they are used. Feel free to create an issue if you really need it.

---

Let the rocket start. Made with ♥ by Christoph Lehmann