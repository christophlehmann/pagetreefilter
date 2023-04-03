class PageTreeFilter
{
    constructor()
    {
        this.waitForElement('#typo3-pagetree .svg-toolbar__menu').then((element) => {
            if (!element.dataset.pageTreeFilterLoaded) {
                element.dataset.pageTreeFilterLoaded = true;
                TYPO3.Icons.getIcon('actions-rocket', 'small').then((icon) => {
                    element.insertAdjacentHTML('beforeend',
                        '<button id="pagetreefilter" class="btn btn-default btn-borderless btn-sm" title="' + TYPO3.lang.pagetreefilter_button_title + '">' +
                        '<span class="icon icon-size-small icon-state-default">' +
                        '<span class="icon-markup">' + icon + '</span>' +
                        '</span>' +
                        '</button>'
                    );
                    document.querySelector('#pagetreefilter').onclick = () => {
                        this.openWizard();
                    }
                });
            }
        })
    }

    waitForElement = (selector) =>
    {
        return new Promise(resolve => {
            if (document.querySelector(selector)) {
                return resolve(document.querySelector(selector));
            }

            const observer = new MutationObserver(mutations => {
                if (document.querySelector(selector)) {
                    resolve(document.querySelector(selector));
                    observer.disconnect();
                }
            });

            observer.observe(document.body, {
                childList: true,
                subtree: true
            });
        });
    }

    openWizard = () => {
        TYPO3.Modal.advanced({
            size: 'medium',
            type: 'ajax',
            title: TYPO3.lang.pagetreefilter_wizard_title,
            severity: TYPO3.Severity.info,
            ajaxCallback: (modal) => {
                const links = document.querySelectorAll('.pagetreefilter-link');
                links.forEach((button) => {
                    button.addEventListener('click', () => {
                        this.applyFilter(button.getAttribute('data-pagetreefilter'));
                        TYPO3.Modal.dismiss()
                    });
                });
                this.toggleHideUnusedElements(modal);
            },
            content: TYPO3.settings.ajaxUrls.pagetreefilter_fetch_filter,
            additionalCssClasses: ['pagetreefilter-wizard'],
            buttons: [
                {
                    text: TYPO3.lang.pagetreefilter_button_text_show_unused,
                    name: 'pagetreefilter-wizard-show-unused',
                    icon: 'actions-toggle-off',
                    active: true,
                    btnClass: 'btn-default',
                    trigger: (event, modal) => {
                        this.toggleHideUnusedElements(modal)
                    }
                }
            ]
        });
    }

    toggleHideUnusedElements = (modal) => {
        const items = modal.querySelectorAll('.pagetreefilter-wizard-item');
        const buttonIcon = modal.querySelector('button[name="pagetreefilter-wizard-show-unused"] typo3-backend-icon');

        const hiddenItems = modal.querySelectorAll('.hide');
        hiddenItems.forEach(function (item) {
            item.classList.remove('hide');
        });

        if (modal.dataset.hideDisabled) {
            delete modal.dataset.hideDisabled;
            items.forEach(function(item){
                if (item.classList.contains('disabled')) {
                    item.classList.remove('hide');
                } else {
                    item.classList.add('hide');
                }
            });
            buttonIcon.setAttribute('identifier', 'actions-toggle-on');
        } else {
            modal.dataset.hideDisabled = true;
            items.forEach(function(item){
                if (item.classList.contains('disabled')) {
                    item.classList.add('hide');
                } else {
                    item.classList.remove('hide');
                }
            });
            buttonIcon.setAttribute('identifier', 'actions-toggle-off');
        }

        const tabs = modal.querySelectorAll('a.nav-link');
        tabs.forEach(function(tab) {
            const identifier = tab.getAttribute('aria-controls');
            const visibleItemsInTab = modal.querySelectorAll('#' + identifier + ' .pagetreefilter-wizard-item:not(.hide)');
            if (visibleItemsInTab.length === 0) {
                console.log('hide tab', tab);
                tab.parentNode.classList.add('hide');
            }
        });

        const activeTabHidden = modal.querySelector('.nav-item.hide a.nav-link.active');
        if (activeTabHidden) {
            activeTabHidden.classList.remove('active');
            modal.querySelector('#' + activeTabHidden.getAttribute('aria-controls')).classList.remove('active');

            const firstNonHiddenTab = modal.querySelector('.nav-item:not(.hide) a.nav-link');
            if (firstNonHiddenTab) {
                firstNonHiddenTab.classList.add('active');
                modal.querySelector('#' + firstNonHiddenTab.getAttribute('aria-controls')).classList.add('active');
            }
        }
    }

    applyFilter = (filter) => {
        document.querySelector('#typo3-pagetree .search-input').value = filter;
        document.querySelector('#typo3-pagetree-tree').filter(filter)
    }
}

export default new PageTreeFilter();