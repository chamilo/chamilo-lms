(function () {
    'use strict';

    function initializeOwnerSearch() {
        const select = document.querySelector('select[name="owner_id"]');
        if (!select || select.dataset.buycoursesOwnerSearchInitialized === '1') {
            return;
        }

        select.dataset.buycoursesOwnerSearchInitialized = '1';

        const search = document.createElement('input');
        search.type = 'search';
        search.autocomplete = 'off';
        search.className = 'buycourses-control mb-2';
        search.placeholder = select.dataset.buycoursesOwnerSearchPlaceholder || 'Search owner';
        search.setAttribute('aria-label', search.placeholder);
        search.style.marginBottom = '0.5rem';

        const status = document.createElement('div');
        status.setAttribute('role', 'status');
        status.setAttribute('aria-live', 'polite');
        status.className = 'form-text text-muted';

        select.parentNode.insertBefore(search, select);
        select.insertAdjacentElement('afterend', status);

        let delay;
        let controller;
        let generation = 0;

        function updateOptions(users) {
            const selectedValue = select.value;
            const selectedOption = Array.from(select.options).find(function (option) {
                return option.value === selectedValue;
            });
            const emptyOption = Array.from(select.options).find(function (option) {
                return option.value === '0' || option.value === '';
            });

            select.replaceChildren();
            if (emptyOption) {
                select.add(new Option(emptyOption.text, emptyOption.value));
            }
            if (selectedOption && selectedValue !== '' && selectedValue !== '0') {
                select.add(new Option(selectedOption.text, selectedValue));
            }

            users.forEach(function (user) {
                const id = String(user.id);
                if (!Array.from(select.options).some(function (option) { return option.value === id; })) {
                    select.add(new Option(String(user.text), id));
                }
            });

            select.value = selectedValue;
        }

        search.addEventListener('input', function () {
            clearTimeout(delay);
            if (controller) {
                controller.abort();
            }

            const requestNumber = ++generation;
            const keyword = search.value.trim();
            status.textContent = '';

            if (keyword.length < 2) {
                updateOptions([]);
                return;
            }

            delay = setTimeout(function () {
                controller = new AbortController();
                const endpoint = select.dataset.buycoursesOwnerSearchUrl || 'service_owner_search.php';
                const url = new URL(endpoint, window.location.href);
                url.searchParams.set('q', keyword);

                fetch(url.toString(), {
                    credentials: 'same-origin',
                    headers: {'Accept': 'application/json'},
                    signal: controller.signal
                })
                    .then(function (response) {
                        if (!response.ok) {
                            throw new Error('Owner search request failed');
                        }
                        return response.json();
                    })
                    .then(function (data) {
                        if (requestNumber !== generation) {
                            return;
                        }
                        const users = Array.isArray(data.results) ? data.results : [];
                        updateOptions(users);
                        status.textContent = String(users.length) + ' / 20';
                    })
                    .catch(function (error) {
                        if (error.name !== 'AbortError' && requestNumber === generation) {
                            status.textContent = select.dataset.buycoursesOwnerSearchError || 'Search failed';
                        }
                    });
            }, 300);
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initializeOwnerSearch);
    } else {
        initializeOwnerSearch();
    }
}());
