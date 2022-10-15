require([], function () {

    function initializeFilter() {
        let toolbar = document.querySelector('#typo3-pagetree .svg-toolbar__menu');
        if(toolbar === null) {
            // @todo: listen on event 'svg-tree:initialized' in SvgTree
            setTimeout(initializeFilter, 100);
            return;
        }

        TYPO3.Icons.getIcon('actions-rocket', 'small').then((icon) => {
            toolbar.insertAdjacentHTML('beforeend',
            '<button id="pagetreefilter" class="btn btn-default btn-borderless btn-sm" title="' + TYPO3.lang.pagetreefilter_button_title + '">' +
                '<span class="icon icon-size-small icon-state-default">' +
                    '<span class="icon-markup">' + icon + '</span>' +
                '</span>' +
            '</button>'
        );
        document.querySelector('#pagetreefilter').onclick = function() { openWizard(); }
        });
    }

    function openWizard()
    {
        TYPO3.Modal.advanced({
            size: 'medium',
            type: 'ajax',
            title: TYPO3.lang.pagetreefilter_wizard_title,
            severity: TYPO3.Severity.info,
            ajaxCallback: function () {
                let links = document.querySelectorAll('a.pagetreefilter');
                links.forEach((button) => {
                    button.addEventListener('click', () => {
                        let filterInput = document.querySelector('.pagetreefilter-wizard #pagetreefilter-filter');
                        let newFilter = button.getAttribute('data-pagetreefilter');
                        if (filterInput.value === newFilter) {
                            applyFilter();
                        }
                        filterInput.value = newFilter;
                        filterInput.focus();
                    });
                });
                toggleHideUnusedElements();
                document.querySelector('#pagetreefilterhideunused').addEventListener('click', () => {
                    toggleHideUnusedElements()
                });

                let footer = `  
                    <div class="t3js-modal-footer modal-footer">
                        <form id="pagetreefilter-form">
                            <div class="row">
                                <div class="col-lg-9"><input type="text" class="form-control" id="pagetreefilter-filter" placeholder="` + TYPO3.lang.pagetreefilter_wizard_input_placeholder + `"></div>
                                <div class="col-lg-3"><button class="btn btn-primary"><span>` + TYPO3.lang.pagetreefilter_wizard_submit_button + `</span></button></div>
                            </div>
                        </form>
                    </div>
                `
                document.querySelector('.pagetreefilter-wizard .modal-content').insertAdjacentHTML('beforeend', footer);
                document.querySelector('#pagetreefilter-form').addEventListener('submit', applyFilter);
            },
            content: TYPO3.settings.ajaxUrls.pagetreefilter_fetch_filter,
            additionalCssClasses: ['pagetreefilter-wizard']
        });
    }

    function toggleHideUnusedElements()
    {
        const wizard = document.querySelector('.pagetreefilter-wizard');
        const isHidden = wizard.querySelector('.hide');
        if (!isHidden) {
            wizard.querySelectorAll('a.nav-link').forEach(function (tab) {
                const tabContentIdentifier = tab.getAttribute('aria-controls');

                wizard.querySelectorAll('a.pagetreefilter.disabled').forEach(function(item) {
                    item.parentNode.classList.add('hide');
                })

                const hasVisibleItems = wizard.querySelector('#' + tabContentIdentifier + ' a.pagetreefilter:not(.disabled)');
                if (!hasVisibleItems) {
                    tab.classList.add('hide');
                }
            });

            const activeTabIsHidden = wizard.querySelector('a.nav-link.active.hide');
            if (activeTabIsHidden) {
               wizard.querySelectorAll('.active').forEach(function (activeItem) {
                   activeItem.classList.remove('active');
               });
               const firstNonHiddenTab = wizard.querySelector('a.nav-link:not(.hide)');
               firstNonHiddenTab.classList.add('active');
               wizard.querySelector('#' + firstNonHiddenTab.getAttribute('aria-controls')).classList.add('active');
            }
        } else {
            wizard.querySelectorAll('.hide').forEach(function (hiddenItem) {
                hiddenItem.classList.remove('hide');
            });
        }
    }

    function applyFilter(event) {
        if (event) {
            event.preventDefault();
        }
        let filter = document.querySelector('#pagetreefilter-filter').value;
        document.querySelector('#typo3-pagetree .search-input').value = filter;
        document.querySelector('#typo3-pagetree-tree').filter(filter);
        TYPO3.Modal.dismiss();
    }

    initializeFilter();
});