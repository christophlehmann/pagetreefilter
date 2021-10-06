require([], function () {

    function initializeFilter() {
        let toolbar = document.querySelectorAll('#typo3-pagetree .svg-toolbar__menu').item(0);
        if(toolbar === null) {
            // @todo: listen on event 'svg-tree:initialized' in SvgTree
            setTimeout(initializeFilter, 1000);
            return;
        }

        let icon = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16"><g class="icon-color"><path d="M15 1C13.3-.7 6 5 6 5H2L0 7l2 2 1 1-2 2 3 3 2-2 1 1 2 2 2-2v-4s5.7-7.3 4-9zM2.7 8.3L1.4 7l1-1H5L2.7 8.3zm7.3 5.3l-1 1-1.3-1.3L10 11v2.6zm.3-4.3L7 12.6l-.3-.3-.7-.7-.7.7L4 13.6 2.4 12l1.3-1.3.7-.7-.7-.7-.3-.3 3.3-3.3c2-1.6 5.8-4.1 7.4-4.1h.2c.4.7-1.2 4.2-4 7.7z"/><path d="M11 4c-.6 0-1 .4-1 1s.4 1 1 1 1-.4 1-1-.4-1-1-1zM1 15H0v1h1v-1zM2 14H1v1h1v-1zM1 13H0v1h1v-1zM3 15H2v1h1v-1z"/></g></svg>';
        let buttonGroup = toolbar.querySelectorAll('.btn-group').item(0)
        if (buttonGroup !== null) {
            // TYPO3 10
            buttonGroup.insertAdjacentHTML('beforeend', '' +
                '<div class="x-btn btn btn-default btn-sm x-btn-noicon">' +
                    '<button class="svg-toolbar__btn" id="pagetreefilter" title="' + TYPO3.lang.pagetreefilter_button_title + '">' +
                    '<span class="icon icon-size-small icon-state-default">' +
                        '<span class="icon-markup">' + icon + '</span>' +
                    '</span>' +
                    '</button>' +
                '</div>'
            );
        } else {
            toolbar.insertAdjacentHTML('beforeend', '' +
                '<button id="pagetreefilter" class="btn btn-default btn-borderless btn-sm" title="' + TYPO3.lang.pagetreefilter_button_title + '">' +
                    '<span class="icon icon-size-small icon-state-default">' +
                        '<span class="icon-markup">' + icon + '</span>' +
                    '</span>' +
                '</button>'
            );
        }

        document.querySelector('#pagetreefilter').onclick = function() { openWizard(); }
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
                        applyFilter(button.getAttribute('data-pagetreefilter'));
                        TYPO3.Modal.dismiss()
                    });
                });
            },
            content: TYPO3.settings.ajaxUrls.pagetreefilter_fetch_filter,
            additionalCssClasses: ['pagetreefilter-wizard']
        });
    }

    function applyFilter(filter)
    {
        let searchField = document.querySelector('#typo3-pagetree .search-input');
        searchField.value = filter;

        if (typeof document.querySelector('#typo3-pagetree-tree').filter === 'function') {
            document.querySelector('#typo3-pagetree-tree').filter(filter)
        } else {
            // TYPO3 10
            TYPO3.Backend.NavigationContainer.PageTree.instance.searchQuery = filter;
            TYPO3.Backend.NavigationContainer.PageTree.instance.filterTree();
        }
    }

    initializeFilter();
});