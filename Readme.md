
TYPO3 Extension: Pagetree Filter
================================

It adds a wizard like the new content element wizard that helps finding content elements and records in the page tree. With a few clicks you know where they are used. You can also see what elements are not used.

## Screenshots

### Highlight pages with image elements

![Example](https://raw.githubusercontent.com/christophlehmann/pagetreefilter/master/Documentation/Images/filter-example.png)

### The filter wizard

![The Wizard](https://raw.githubusercontent.com/christophlehmann/pagetreefilter/master/Documentation/Images/filter-wizard.png)

## Example filters

Highlight pages with...

* sys_category records: `table=sys_category`
* image content elements: `table=tt_content CType=image`
* hidden image content elements: `table=tt_content CType=image hidden=1`
* content elements where the elements header starts with rocket: `table=tt_content header=rocket*`
* content elements where the elements header ends with launch: `table=tt_content header=*launch`
* content elements where the elements header contains rocketlaunch: `table=tt_content header=*rocketlaunch*`

## Installation

`composer require christophlehmann/pagetreefilter`

## Configuration

### Enable for non admin users (User TSconfig)

`tx_pagetreefilter.enable = 1`

### PageId

The wizard needs a page id for fetching Page TSconfig in order to gather possible content elements and plugins. This page id is determined by

1. First WebMount in case the user is no admin
2. Extension configuration: `pageId` (default: `1`)

---

Let the rocket start. Made with ♥ by Christoph Lehmann