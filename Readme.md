
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

### ignoreFields (Page TSconfig)

The wizard creates a filter based on `tt_content_defValues`, see `mod.wizards.newContentElement.wizardItems`.
If you don't like them, then you can ignore them:

`tx_pagetreefilter.buildFilter.ignoreFields = tx_gridelements_columns`

### PageId

The wizard needs a page id for fetching Page TSconfig in order to gather possible content elements and plugins. This page id is determined by

1. First WebMount in case the user is no admin
2. Extension configuration: `pageId` (default: `1`)

### Use translated title in search into pageTree

Currently, searching in pagetree use only default language, we add new feature to allow users to find the pages by insert the uid or the title of the translated page

* By default, they use the value 0 that mean default language in the website
* To add another language in pagetree search, you should just add the uid of language that you use and seperated by comma in the Backend User TsConfig or the Group Backend User TsConfig like that

`tx_pagetreefilter.used_language_uid = 0,1`


---

Let the rocket start. Made with ♥ by Christoph Lehmann