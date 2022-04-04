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
        document.querySelector('#typo3-pagetree .search-input').value = filter;
        document.querySelector('#typo3-pagetree-tree').filter(filter)
    }

    initializeFilter();
});