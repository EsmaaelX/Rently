/**
 * Rently — Main Client-Side Application
 * Features: Dark Mode, Search + Autocomplete, AJAX Booking, Wishlist,
 * Notifications, Reviews, Lazy Loading, Auto-Save, Code Inputs, Dynamic Filters
 */
(function () {
    'use strict';

    const R = window.RENTLY || {};
    const BASE = R.baseUrl || '/Rently/';
    const T = R.translations || {};
    const t = (key) => T[key] || key;

    // ─── Init ─────────────────────────────────
    document.addEventListener('DOMContentLoaded', () => {
        initThemeToggle();
        initNotificationBadge();
        initSearch();
        initAutocomplete();
        initCategoryPills();
        initDynamicFilters();
        initBooking();
        initReviews();
        initWishlist();
        initCodeInputs();
        initNotificationActions();
        initReportForm();
        initCancelBooking();
        initToggle2FA();
        initLazyLoading();
        initAutoSave();
        loadRecommendations();
        initAssetMaps();
        initDynamicCategoryFields();
    });

    // ─── Dynamic Category Fields ──────────────
    function initDynamicCategoryFields() {
        const addSelect = document.getElementById('add-category');
        if (addSelect) {
            addSelect.addEventListener('change', (e) => renderExtraFields(e.target.value, document.getElementById('add-extra-fields')));
            renderExtraFields(addSelect.value, document.getElementById('add-extra-fields'));
        }

        document.querySelectorAll('.edit-category-select').forEach(select => {
            const id = select.dataset.id;
            const container = document.getElementById('edit-extra-fields-' + id);
            let initialData = {};
            try { initialData = JSON.parse(select.dataset.extra || '{}'); } catch(e){}
            
            select.addEventListener('change', (e) => renderExtraFields(e.target.value, container, {}));
            renderExtraFields(select.value, container, initialData);
        });
    }

    function renderExtraFields(category, container, data = {}) {
        if (!container) return;
        let html = '';
        
        switch (category) {
            case 'apartment':
                html += `
                    <div class="col-md-4"><label class="form-label fw-semibold">${t('rooms')}</label><input type="number" class="form-control" name="rooms" value="${data.rooms || ''}"></div>
                    <div class="col-md-4"><label class="form-label fw-semibold">${t('size_sqm')}</label><input type="number" class="form-control" name="size_sqm" value="${data.size_sqm || ''}"></div>
                    <div class="col-md-4"><label class="form-label fw-semibold">${t('floor')}</label><input type="number" class="form-control" name="floor" value="${data.floor || ''}"></div>
                    <div class="col-12"><label class="form-label fw-semibold">${t('amenities')}</label><input type="text" class="form-control" name="amenities" placeholder="${t('amenities_placeholder') || 'WiFi, AC, Pool...'}" value="${(data.amenities || []).join(', ')}"></div>
                `;
                break;
            case 'car':
                 html += `
                    <div class="col-md-4"><label class="form-label fw-semibold">${t('make')}</label><input type="text" class="form-control" name="car_make" value="${data.make || ''}"></div>
                    <div class="col-md-4"><label class="form-label fw-semibold">${t('model')}</label><input type="text" class="form-control" name="car_model" value="${data.model || ''}"></div>
                    <div class="col-md-4"><label class="form-label fw-semibold">${t('year')}</label><input type="number" class="form-control" name="car_year" value="${data.year || ''}"></div>
                    <div class="col-md-4"><label class="form-label fw-semibold">${t('seats')}</label><input type="number" class="form-control" name="seats" value="${data.seats || ''}"></div>
                    <div class="col-md-4"><label class="form-label fw-semibold">${t('transmission')}</label>
                        <select class="form-select" name="transmission">
                            <option value="Automatic" ${data.transmission === 'Automatic' ? 'selected' : ''}>${t('automatic') || 'Automatic'}</option>
                            <option value="Manual" ${data.transmission === 'Manual' ? 'selected' : ''}>${t('manual') || 'Manual'}</option>
                        </select>
                    </div>
                    <div class="col-md-4"><label class="form-label fw-semibold">${t('fuel')}</label><input type="text" class="form-control" name="fuel" value="${data.fuel || ''}"></div>
                `;
                break;
            case 'sport_venue':
                html += `
                    <div class="col-md-6"><label class="form-label fw-semibold">${t('sport_type')}</label><input type="text" class="form-control" name="sport_type" value="${data.sport_type || ''}"></div>
                    <div class="col-md-6"><label class="form-label fw-semibold">${t('capacity')}</label><input type="number" class="form-control" name="capacity" value="${data.capacity || ''}"></div>
                    <div class="col-md-6 border rounded p-2 mt-3 ms-2">
                        <div class="form-check form-switch"><input class="form-check-input" type="checkbox" name="indoor" id="indoor_${container.id}" ${data.indoor ? 'checked' : ''}><label class="form-check-label fw-semibold" for="indoor_${container.id}">${t('indoor')}</label></div>
                    </div>
                `;
                break;
            case 'equipment':
                html += `
                    <div class="col-md-6"><label class="form-label fw-semibold">${t('type')}</label><input type="text" class="form-control" name="equipment_type" value="${data.equipment_type || ''}"></div>
                    <div class="col-md-6"><label class="form-label fw-semibold">${t('brand')}</label><input type="text" class="form-control" name="equipment_brand" value="${data.brand || ''}"></div>
                `;
                break;
             case 'studio':
                html += `
                    <div class="col-md-6"><label class="form-label fw-semibold">${t('type')}</label><input type="text" class="form-control" name="studio_type" value="${data.studio_type || ''}"></div>
                    <div class="col-md-6"><label class="form-label fw-semibold">${t('size_sqm')}</label><input type="number" class="form-control" name="size_sqm" value="${data.size_sqm || ''}"></div>
                `;
                break;
        }
        container.innerHTML = html;
    }

    // ─── Map Integration ──────────────────────
    function initAssetMaps() {
        if (typeof L === 'undefined') return;

        function setupMap(mapId, latInputId, lngInputId, addressInputId, cityInputId, searchInputId, searchBtnId, initLat, initLng) {
            const container = document.getElementById(mapId);
            if (!container) return;

            let startLat = initLat || 32.0853;
            let startLng = initLng || 34.7818;
            let zoom = initLat ? 15 : 10;

            const map = L.map(mapId).setView([startLat, startLng], zoom);
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '© OpenStreetMap'
            }).addTo(map);

            let marker = null;
            if (initLat && initLng) {
                marker = L.marker([startLat, startLng]).addTo(map);
            }

            function setPin(lat, lng, label) {
                if (marker) map.removeLayer(marker);
                marker = L.marker([lat, lng]).addTo(map);
                map.setView([lat, lng], 16);
                
                document.getElementById(latInputId).value = lat;
                document.getElementById(lngInputId).value = lng;
                
                // Reverse Geocode
                fetch('https://nominatim.openstreetmap.org/reverse?format=json&lat=' + lat + '&lon=' + lng)
                    .then(r => r.json())
                    .then(data => {
                        if (data && data.address) {
                            const city = data.address.city || data.address.town || data.address.village || '';
                            const street = data.address.road || '';
                            const num = data.address.house_number || '';
                            
                            document.getElementById(cityInputId).value = city;
                            document.getElementById(addressInputId).value = (street + ' ' + num).trim() || data.display_name;
                            
                            if(label) document.getElementById(searchInputId).value = label;
                            else document.getElementById(searchInputId).value = data.display_name;
                        }
                    });
            }

            map.on('click', function(e) {
                setPin(e.latlng.lat, e.latlng.lng);
            });

            const searchBtn = document.getElementById(searchBtnId);
            const searchInput = document.getElementById(searchInputId);
            if (searchBtn && searchInput) {
                searchBtn.addEventListener('click', () => {
                    const query = searchInput.value;
                    if (!query) return;
                    fetch('https://nominatim.openstreetmap.org/search?format=json&limit=1&q=' + encodeURIComponent(query))
                        .then(r => r.json())
                        .then(data => {
                            if (data && data.length > 0) {
                                setPin(data[0].lat, data[0].lon, data[0].display_name);
                            } else {
                                showToast(t('error') || 'Not found', 'error');
                            }
                        });
                });
                searchInput.addEventListener('keydown', (e) => {
                    if (e.key === 'Enter') {
                        e.preventDefault();
                        searchBtn.click();
                    }
                });
            }

            // Fix map size in modal
            const modal = container.closest('.modal');
            if (modal) {
                modal.addEventListener('shown.bs.modal', () => map.invalidateSize());
            }
        }

        // Init Add Modal Map
        setupMap('add-map', 'add-lat', 'add-lng', 'add-address', 'add-city', 'add-map-search', 'add-map-search-btn', null, null);

        // Init Edit Modal Maps
        document.querySelectorAll('.edit-map-container').forEach(container => {
            const id = container.dataset.id;
            setupMap('edit-map-' + id, 'edit-lat-' + id, 'edit-lng-' + id, 'edit-address-' + id, 'edit-city-' + id, 'edit-map-search-' + id, 'edit-map-search-btn-' + id, container.dataset.lat, container.dataset.lng);
        });
    }

    // ─── Dark Mode Toggle ─────────────────────
    function initThemeToggle() {
        const btn = document.getElementById('theme-toggle');
        if (!btn) return;

        // Apply saved theme on load
        const saved = getCookie('rently_theme');
        if (saved === 'dark') {
            applyTheme('dark');
        }

        btn.addEventListener('click', () => {
            const html = document.documentElement;
            const current = html.getAttribute('data-bs-theme') || 'light';
            const next = current === 'dark' ? 'light' : 'dark';
            applyTheme(next);
            setCookie('rently_theme', next, 365);
        });
    }

    function applyTheme(theme) {
        const html = document.documentElement;
        const btn = document.getElementById('theme-toggle');

        html.setAttribute('data-bs-theme', theme);

        // Also add/remove body class as CSS fallback
        document.body.classList.toggle('dark-theme', theme === 'dark');
        document.body.classList.toggle('light-theme', theme === 'light');

        if (btn) {
            const icon = btn.querySelector('i');
            if (icon) {
                icon.className = theme === 'dark' ? 'bi bi-sun-fill' : 'bi bi-moon-fill';
            }
            btn.title = theme === 'dark' ? t('light_mode') : t('dark_mode');
        }
    }

    // ─── Cookie Helpers ───────────────────────
    function setCookie(name, value, days) {
        const d = new Date();
        d.setTime(d.getTime() + (days * 24 * 60 * 60 * 1000));
        document.cookie = name + '=' + value + ';expires=' + d.toUTCString() + ';path=/';
    }

    function getCookie(name) {
        const match = document.cookie.match(new RegExp('(^| )' + name + '=([^;]+)'));
        return match ? match[2] : null;
    }

    // ─── Notification Badge ───────────────────
    function initNotificationBadge() {
        if (!R.isLoggedIn) return;
        const badge = document.getElementById('notif-badge');
        if (!badge) return;

        const check = () => {
            ajaxGet(BASE + 'index.php?page=notifications&action=unread-count', (data) => {
                if (data && data.count > 0) {
                    badge.textContent = data.count;
                    badge.classList.remove('d-none');
                } else {
                    badge.classList.add('d-none');
                }
            });
        };
        check();
        setInterval(check, 30000);
    }

    // ─── Smart Search ─────────────────────────
    function initSearch() {
        const form = document.getElementById('search-form');
        if (!form) return;

        form.addEventListener('submit', (e) => {
            e.preventDefault();
            performSearch();
        });

        // Also search on filter changes
        ['filter-category', 'filter-sort'].forEach(id => {
            const el = document.getElementById(id);
            if (el) el.addEventListener('change', performSearch);
        });

        // Pre-fill search if category is in URL
        const urlParams = new URLSearchParams(window.location.search);
        const categoryParam = urlParams.get('category');
        if (categoryParam) {
            const catSelect = document.getElementById('filter-category');
            if (catSelect) {
                catSelect.value = categoryParam;
                updateDynamicFilters(categoryParam);
                
                // Sync the Pills
                document.querySelectorAll('#category-pills .category-pill').forEach(p => {
                    p.classList.toggle('active', p.dataset.category === categoryParam);
                });
                
                // Fire Search
                setTimeout(performSearch, 50);
            }
        }
    }

    function performSearch() {
        const params = new URLSearchParams();
        const fields = {
            keyword: 'filter-keyword',
            category: 'filter-category',
            location: 'filter-location',
            sort: 'filter-sort',
            min_price: 'filter-min-price',
            max_price: 'filter-max-price',
            start_date: 'filter-start',
            end_date: 'filter-end'
        };

        Object.entries(fields).forEach(([param, id]) => {
            const el = document.getElementById(id);
            if (el && el.value) params.set(param, el.value);
        });

        // Dynamic filter fields
        document.querySelectorAll('#dynamic-filters [data-filter]').forEach(el => {
            if (el.value) params.set(el.dataset.filter, el.value);
        });

        const grid = document.getElementById('assets-grid');
        if (!grid) return;
        grid.innerHTML = '<div class="col-12 text-center py-5"><div class="spinner-border text-accent"></div></div>';

        ajaxGet(BASE + 'index.php?page=search&' + params.toString(), (data) => {
            if (!data) return;
            const count = document.getElementById('results-count');
            if (count) count.textContent = data.count + ' ' + t('listings');

            if (!data.assets || data.assets.length === 0) {
                grid.innerHTML = '<div class="col-12 text-center py-5"><i class="bi bi-inbox display-3 text-muted d-block mb-3"></i><p class="text-muted">' + t('no_results') + '</p></div>';
                return;
            }

            grid.innerHTML = data.assets.map(a => buildAssetCard(a)).join('');
            initWishlistButtons();
            initLazyLoading();
        });
    }

    function buildAssetCard(a) {
        const isHourly = ['sport_venue', 'studio', 'parking'].includes(a.category);
        const price = isHourly ? a.price_per_hour : a.price_per_day;
        const priceSuffix = isHourly ? t('per_hour') : t('per_day');
        const avgRating = parseFloat(a.avg_rating) || 0;
        const icons = {
            apartment: 'bi-building', car: 'bi-car-front', sport_venue: 'bi-trophy',
            equipment: 'bi-tools', studio: 'bi-easel2', parking: 'bi-p-circle'
        };

        const imgSrc = a.image_url && a.image_url.startsWith('http') ? escHtml(a.image_url) : BASE + escHtml(a.image_url);
        const imgHtml = a.image_url
            ? '<img src="' + imgSrc + '" class="card-img-top" alt="' + escHtml(a.title) + '" loading="lazy">'
            : '<div class="card-img-top placeholder-img d-flex align-items-center justify-content-center"><i class="bi ' + (icons[a.category] || 'bi-box') + ' display-3 text-muted"></i></div>';

        let starsHtml = '';
        if (avgRating > 0) {
            let stars = '';
            for (let i = 1; i <= 5; i++) {
                stars += '<i class="bi bi-star' + (i <= Math.round(avgRating) ? '-fill' : '') + '"></i>';
            }
            starsHtml = '<div class="mb-2"><span class="stars-mini">' + stars + '</span> <small class="text-muted ms-1">' + avgRating.toFixed(1) + '</small></div>';
        }

        let wishBtn = '';
        if (R.isLoggedIn && !R.isAdmin) {
            wishBtn = '<button class="wishlist-btn' + (a.in_wishlist ? ' active' : '') + '" data-asset-id="' + a.asset_id + '" title="' + t('wishlist') + '"><i class="bi bi-heart' + (a.in_wishlist ? '-fill' : '') + '"></i></button>';
        }

        return '<div class="col-lg-4 col-md-6">' +
            '<div class="asset-card card h-100 border-0 shadow-sm">' +
            '<div class="asset-card-img-wrapper">' + imgHtml +
            '<span class="category-badge badge">' + t(a.category) + '</span>' +
            wishBtn + '</div>' +
            '<div class="card-body d-flex flex-column">' +
            '<h5 class="card-title fw-bold">' + escHtml(a.title) + '</h5>' +
            '<p class="text-muted small mb-1"><i class="bi bi-geo-alt me-1"></i>' + escHtml(a.address || a.city || '') + '</p>' +
            '<p class="text-muted small mb-2"><i class="bi bi-person me-1"></i>' + t('hosted_by') + ' ' + escHtml(a.owner_name) + '</p>' +
            starsHtml +
            '<div class="mt-auto d-flex justify-content-between align-items-center">' +
            '<div class="price-tag"><strong class="text-accent fs-5">$' + parseFloat(price).toFixed(2) + '</strong> <small class="text-muted">' + priceSuffix + '</small></div>' +
            '<a href="' + BASE + 'index.php?page=asset&action=detail&id=' + a.asset_id + '" class="btn btn-sm btn-outline-accent">' + t('view_details') + ' <i class="bi bi-arrow-right ms-1"></i></a>' +
            '</div></div></div></div>';
    }

    // ─── Autocomplete ─────────────────────────
    function initAutocomplete() {
        const input = document.getElementById('filter-keyword');
        const dropdown = document.getElementById('autocomplete-dropdown');
        if (!input || !dropdown) return;

        let timer;
        input.addEventListener('input', () => {
            clearTimeout(timer);
            const q = input.value.trim();
            if (q.length < 2) { dropdown.classList.remove('show'); return; }

            timer = setTimeout(() => {
                ajaxGet(BASE + 'index.php?page=autocomplete&q=' + encodeURIComponent(q), (data) => {
                    if (!data || !data.suggestions || data.suggestions.length === 0) {
                        dropdown.classList.remove('show');
                        return;
                    }
                    dropdown.innerHTML = data.suggestions.map(s =>
                        '<div class="autocomplete-item" data-text="' + escHtml(s.text) + '">' +
                        '<span class="icon">' + s.icon + '</span>' +
                        '<span class="text">' + escHtml(s.text) + '</span>' +
                        '<span class="meta">' + escHtml(s.city || '') + ' · ' + t(s.category) + '</span>' +
                        '</div>'
                    ).join('');
                    dropdown.classList.add('show');

                    dropdown.querySelectorAll('.autocomplete-item').forEach(item => {
                        item.addEventListener('click', () => {
                            input.value = item.dataset.text;
                            dropdown.classList.remove('show');
                            performSearch();
                        });
                    });
                });
            }, 300);
        });

        document.addEventListener('click', (e) => {
            if (!input.contains(e.target) && !dropdown.contains(e.target)) {
                dropdown.classList.remove('show');
            }
        });
    }

    // ─── Category Pills ───────────────────────
    function initCategoryPills() {
        document.querySelectorAll('#category-pills .category-pill').forEach(pill => {
            pill.addEventListener('click', () => {
                document.querySelectorAll('#category-pills .category-pill').forEach(p => p.classList.remove('active'));
                pill.classList.add('active');
                const cat = pill.dataset.category;
                const sel = document.getElementById('filter-category');
                if (sel) sel.value = cat;
                updateDynamicFilters(cat);
                performSearch();
            });
        });
    }

    // ─── Dynamic Filters ──────────────────────
    function initDynamicFilters() {
        const catSelect = document.getElementById('filter-category');
        if (!catSelect) return;
        catSelect.addEventListener('change', () => updateDynamicFilters(catSelect.value));
    }

    function updateDynamicFilters(category) {
        const container = document.getElementById('dynamic-filters');
        if (!container) return;

        const filters = {
            apartment: '<div class="col-md-4"><label class="form-label small fw-semibold">' + t('rooms') + '</label>' +
                '<input type="number" class="form-control form-control-sm" data-filter="rooms" min="1" placeholder="Min rooms"></div>',
            car: '<div class="col-md-3"><label class="form-label small fw-semibold">' + t('car_year') + '</label>' +
                '<input type="number" class="form-control form-control-sm" data-filter="car_year" placeholder="e.g. 2024"></div>' +
                '<div class="col-md-3"><label class="form-label small fw-semibold">' + t('car_make') + '</label>' +
                '<input type="text" class="form-control form-control-sm" data-filter="car_make" placeholder="e.g. Tesla"></div>',
            sport_venue: '<div class="col-md-4"><label class="form-label small fw-semibold">' + t('sport_type') + '</label>' +
                '<input type="text" class="form-control form-control-sm" data-filter="sport_type" placeholder="e.g. Basketball"></div>'
        };

        if (filters[category]) {
            container.innerHTML = '<div class="row g-3">' + filters[category] + '</div>';
            container.classList.remove('collapsed');
            container.classList.add('expanded');
        } else {
            container.classList.remove('expanded');
            container.classList.add('collapsed');
            setTimeout(() => { container.innerHTML = ''; }, 300);
        }
    }

    // ─── Booking ──────────────────────────────
    function initBooking() {
        const checkBtn = document.getElementById('check-availability-btn');
        const bookBtn = document.getElementById('book-now-btn');
        if (!checkBtn) return;

        const startInput = document.getElementById('book-start');
        const endInput = document.getElementById('book-end');
        const preview = document.getElementById('price-preview');
        const estTotal = document.getElementById('estimated-total');
        const msg = document.getElementById('availability-msg');
        const assetId = checkBtn.dataset.assetId;
        const priceHour = parseFloat(checkBtn.dataset.priceHour) || 0;
        const priceDay = parseFloat(checkBtn.dataset.priceDay) || 0;
        const category = checkBtn.dataset.category;
        const isHourly = ['sport_venue', 'studio', 'parking'].includes(category);

        // Price estimate on date change
        const calcPrice = () => {
            if (!startInput || !endInput) return;
            const s = startInput.value, e = endInput.value;
            if (!s || !e) return;

            let total = 0;
            if (isHourly) {
                const hrs = Math.max(Math.ceil((new Date(e) - new Date(s)) / 3600000), 1);
                total = hrs * priceHour;
            } else {
                const startDate = new Date(s + 'T14:00:00');
                const endDate = new Date(e + 'T11:00:00');
                const days = Math.max(Math.ceil((endDate - startDate) / 86400000), 1);
                total = days * priceDay;
            }
            if (estTotal) estTotal.textContent = '$' + total.toFixed(2);
            if (preview) preview.classList.remove('d-none');
        };
        if (startInput) startInput.addEventListener('change', calcPrice);
        if (endInput) endInput.addEventListener('change', calcPrice);

        // Check availability
        checkBtn.addEventListener('click', () => {
            if (!startInput || !endInput) return;
            const s = startInput.value, e = endInput.value;
            if (!s || !e) { showToast(t('missing_booking_details'), 'error'); return; }

            const start = isHourly ? s : s + ' 14:00:00';
            const end = isHourly ? e : e + ' 11:00:00';

            checkBtn.disabled = true;
            checkBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>' + t('checking');

            ajaxGet(BASE + 'index.php?page=booking&action=check&asset_id=' + assetId + '&start=' + encodeURIComponent(start) + '&end=' + encodeURIComponent(end), (data) => {
                checkBtn.disabled = false;
                checkBtn.innerHTML = '<i class="bi bi-calendar-check me-1"></i>' + t('check_availability');

                if (data && data.available) {
                    if (msg) msg.innerHTML = '<span class="text-success"><i class="bi bi-check-circle me-1"></i>' + data.message + '</span>';
                    if (bookBtn) bookBtn.classList.remove('d-none');
                } else {
                    if (msg) msg.innerHTML = '<span class="text-danger"><i class="bi bi-x-circle me-1"></i>' + (data ? data.message : 'Error') + '</span>';
                    if (bookBtn) bookBtn.classList.add('d-none');
                }
            });
        });

        // Book now
        if (bookBtn) {
            bookBtn.addEventListener('click', () => {
                if (!startInput || !endInput) return;
                const s = startInput.value, e = endInput.value;
                const start = isHourly ? s : s + ' 14:00:00';
                const end = isHourly ? e : e + ' 11:00:00';

                bookBtn.disabled = true;
                bookBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>' + t('processing');

                ajaxPost(BASE + 'index.php?page=booking&action=create', {
                    asset_id: assetId, start_time: start, end_time: end
                }, (data) => {
                    bookBtn.disabled = false;
                    if (data && data.success) {
                        bookBtn.innerHTML = '<i class="bi bi-check-circle me-1"></i>' + t('booked');
                        bookBtn.className = 'btn btn-success w-100';
                        if (msg) msg.innerHTML = '<span class="text-success fw-semibold">' + data.message + '</span>';
                        showToast(data.message, 'success');
                    } else {
                        bookBtn.innerHTML = '<i class="bi bi-credit-card me-1"></i>' + t('book_pay_now');
                        if (msg) msg.innerHTML = '<span class="text-danger">' + (data ? data.error : 'Error') + '</span>';
                        showToast(data ? data.error : 'Error', 'error');
                    }
                });
            });
        }
    }

    // ─── Reviews ──────────────────────────────
    function initReviews() {
        const starRating = document.getElementById('star-rating');
        const submitBtn = document.getElementById('submit-review-btn');
        if (!starRating || !submitBtn) return;

        let rating = 0;
        const stars = starRating.querySelectorAll('i');

        stars.forEach(star => {
            star.addEventListener('click', () => {
                rating = parseInt(star.dataset.value);
                starRating.dataset.rating = rating;
                updateStars(stars, rating);
            });
            star.addEventListener('mouseenter', () => {
                updateStars(stars, parseInt(star.dataset.value));
            });
        });

        starRating.addEventListener('mouseleave', () => {
            updateStars(stars, rating);
        });

        submitBtn.addEventListener('click', () => {
            const comment = document.getElementById('review-comment');
            const assetId = submitBtn.dataset.assetId;

            if (rating === 0) { showToast('Please select a rating', 'error'); return; }

            ajaxPost(BASE + 'index.php?page=review&action=add', {
                asset_id: assetId, rating: rating, comment: comment ? comment.value : ''
            }, (data) => {
                if (data && data.success) {
                    showToast(t('review_submitted'), 'success');
                    setTimeout(() => location.reload(), 1000);
                } else {
                    showToast(data ? data.error : 'Error', 'error');
                }
            });
        });
    }

    function updateStars(stars, activeCount) {
        stars.forEach((s, i) => {
            s.className = i < activeCount ? 'bi bi-star-fill fs-4 active' : 'bi bi-star fs-4';
        });
    }

    // ─── Wishlist ─────────────────────────────
    function initWishlist() {
        initWishlistButtons();

        // Detail page wishlist button
        const detailBtn = document.getElementById('detail-wishlist-btn');
        if (detailBtn) {
            detailBtn.addEventListener('click', (e) => {
                e.preventDefault();
                const assetId = detailBtn.dataset.assetId;
                ajaxPost(BASE + 'index.php?page=wishlist&action=toggle', { asset_id: assetId }, (data) => {
                    if (data && data.success) {
                        const icon = detailBtn.querySelector('i');
                        if (data.action === 'added') {
                            detailBtn.className = 'btn btn-danger flex-fill';
                            if (icon) icon.className = 'bi bi-heart-fill me-1';
                        } else {
                            detailBtn.className = 'btn btn-outline-accent flex-fill';
                            if (icon) icon.className = 'bi bi-heart me-1';
                        }
                        showToast(data.message, 'success');
                    }
                });
            });
        }
    }

    function initWishlistButtons() {
        document.querySelectorAll('.wishlist-btn').forEach(btn => {
            // Remove old listeners by cloning
            const newBtn = btn.cloneNode(true);
            btn.parentNode.replaceChild(newBtn, btn);

            newBtn.addEventListener('click', (e) => {
                e.preventDefault();
                e.stopPropagation();
                const assetId = newBtn.dataset.assetId;

                ajaxPost(BASE + 'index.php?page=wishlist&action=toggle', { asset_id: assetId }, (data) => {
                    if (data && data.success) {
                        const icon = newBtn.querySelector('i');
                        if (data.action === 'added') {
                            newBtn.classList.add('active');
                            if (icon) icon.className = 'bi bi-heart-fill';
                        } else {
                            newBtn.classList.remove('active');
                            if (icon) icon.className = 'bi bi-heart';
                        }
                        showToast(data.message, 'success');
                    }
                });
            });
        });
    }

    // ─── Code Input (Verification / 2FA) ──────
    function initCodeInputs() {
        document.querySelectorAll('.code-input-group').forEach(group => {
            const digits = group.querySelectorAll('.code-digit');
            if (digits.length === 0) return;

            // Find the hidden input — search in parent form
            const form = group.closest('form');
            const hiddenInput = form ? form.querySelector('input[type="hidden"]') : null;

            digits.forEach((input, index) => {
                // Only allow digits
                input.addEventListener('input', (e) => {
                    const val = e.target.value.replace(/\D/g, '');
                    e.target.value = val.slice(0, 1);

                    if (val && index < digits.length - 1) {
                        digits[index + 1].focus();
                    }
                    if (hiddenInput) {
                        hiddenInput.value = Array.from(digits).map(d => d.value).join('');
                    }
                });

                // Handle backspace
                input.addEventListener('keydown', (e) => {
                    if (e.key === 'Backspace' && !e.target.value && index > 0) {
                        digits[index - 1].focus();
                        digits[index - 1].value = '';
                    }
                });

                // Handle paste
                input.addEventListener('paste', (e) => {
                    e.preventDefault();
                    const pasted = (e.clipboardData.getData('text') || '').replace(/\D/g, '').slice(0, 6);
                    pasted.split('').forEach((ch, i) => {
                        if (digits[i]) digits[i].value = ch;
                    });
                    const lastIdx = Math.min(pasted.length, digits.length) - 1;
                    if (lastIdx >= 0) digits[lastIdx].focus();
                    if (hiddenInput) {
                        hiddenInput.value = Array.from(digits).map(d => d.value).join('');
                    }
                });
            });

            // On form submit, assemble code
            if (form) {
                form.addEventListener('submit', () => {
                    if (hiddenInput) {
                        hiddenInput.value = Array.from(digits).map(d => d.value).join('');
                    }
                });
            }
        });
    }

    // ─── Notification Actions ─────────────────
    function initNotificationActions() {
        document.querySelectorAll('.mark-read-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                const id = btn.dataset.id;
                ajaxPost(BASE + 'index.php?page=notifications&action=mark-read', { notification_id: id }, (data) => {
                    if (data && data.success) {
                        const item = btn.closest('.notification-item');
                        if (item) item.classList.remove('unread');
                        btn.remove();
                    }
                });
            });
        });

        const markAllBtn = document.getElementById('mark-all-read-btn');
        if (markAllBtn) {
            markAllBtn.addEventListener('click', () => {
                ajaxPost(BASE + 'index.php?page=notifications&action=mark-all-read', {}, (data) => {
                    if (data && data.success) {
                        document.querySelectorAll('.notification-item.unread').forEach(el => el.classList.remove('unread'));
                        document.querySelectorAll('.mark-read-btn').forEach(b => b.remove());
                        showToast(t('mark_all_read'), 'success');
                    }
                });
            });
        }
    }

    // ─── Report Form ──────────────────────────
    function initReportForm() {
        const btn = document.getElementById('submit-report-btn');
        if (!btn) return;

        btn.addEventListener('click', () => {
            const reasonEl = document.getElementById('report-reason');
            const reason = reasonEl ? reasonEl.value : '';
            if (!reason.trim()) { showToast(t('reason_required'), 'error'); return; }

            ajaxPost(BASE + 'index.php?page=report', {
                asset_id: btn.dataset.assetId, reason: reason
            }, (data) => {
                if (data && data.success) {
                    showToast(data.message, 'success');
                    const modal = document.getElementById('reportModal');
                    if (modal && typeof bootstrap !== 'undefined') {
                        const bsModal = bootstrap.Modal.getInstance(modal);
                        if (bsModal) bsModal.hide();
                    }
                } else {
                    showToast(data ? data.error : 'Error', 'error');
                }
            });
        });
    }

    // ─── Cancel Booking ───────────────────────
    function initCancelBooking() {
        document.querySelectorAll('.cancel-booking-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                if (!confirm(t('cancel_booking') + '?')) return;
                const bookingId = btn.dataset.bookingId;

                ajaxPost(BASE + 'index.php?page=booking&action=cancel', {
                    booking_id: bookingId, reason: 'User requested cancellation'
                }, (data) => {
                    if (data && data.success) {
                        showToast(data.message + ' ($' + data.refund_amount + ' refund)', 'success');
                        setTimeout(() => location.reload(), 1500);
                    } else {
                        showToast(data ? data.error : 'Error', 'error');
                    }
                });
            });
        });
    }

    // ─── Toggle 2FA ───────────────────────────
    function initToggle2FA() {
        const btn = document.getElementById('toggle-2fa-btn');
        if (!btn) return;

        btn.addEventListener('click', () => {
            ajaxPost(BASE + 'index.php?page=toggle-2fa', {}, (data) => {
                if (data && data.success) {
                    showToast(data.message, 'success');
                    setTimeout(() => location.reload(), 1000);
                }
            });
        });
    }

    // ─── Lazy Loading ─────────────────────────
    function initLazyLoading() {
        if (!('IntersectionObserver' in window)) return;

        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('loaded');
                    observer.unobserve(entry.target);
                }
            });
        }, { threshold: 0.1 });

        document.querySelectorAll('.lazy-img:not(.loaded)').forEach(img => observer.observe(img));
    }

    // ─── Auto-Save (localStorage) ─────────────
    function initAutoSave() {
        const form = document.getElementById('search-form');
        if (!form) return;

        const KEY = 'rently_search_state';
        const fieldIds = ['filter-keyword', 'filter-category', 'filter-location', 'filter-sort',
            'filter-min-price', 'filter-max-price', 'filter-start', 'filter-end'];

        // Restore saved state
        try {
            const saved = JSON.parse(localStorage.getItem(KEY));
            if (saved) {
                fieldIds.forEach(id => {
                    const el = document.getElementById(id);
                    if (el && saved[id]) el.value = saved[id];
                });
            }
        } catch (e) { /* ignore parse errors */ }

        // Save on change
        const save = () => {
            const state = {};
            fieldIds.forEach(id => {
                const el = document.getElementById(id);
                if (el) state[id] = el.value;
            });
            try { localStorage.setItem(KEY, JSON.stringify(state)); } catch (e) { }
        };

        fieldIds.forEach(id => {
            const el = document.getElementById(id);
            if (el) {
                el.addEventListener('input', save);
                el.addEventListener('change', save);
            }
        });
    }

    // ─── Load Recommendations ─────────────────
    function loadRecommendations() {
        const grid = document.getElementById('recommendations-grid');
        if (!grid) return;

        ajaxGet(BASE + 'index.php?page=recommendations', (data) => {
            if (!data || !data.assets || data.assets.length === 0) {
                const section = grid.closest('section');
                if (section) section.style.display = 'none';
                return;
            }
            grid.innerHTML = data.assets.map(a => buildAssetCard(a)).join('');
            initWishlistButtons();
        });
    }

    // ─── Toast Notification ───────────────────
    function showToast(message, type) {
        type = type || 'success';
        const container = document.getElementById('toast-container');
        if (!container) return;

        const iconClass = type === 'success' ? 'bi-check-circle-fill text-success' : 'bi-exclamation-circle-fill text-danger';
        const toast = document.createElement('div');
        toast.className = 'toast-custom ' + type;
        toast.innerHTML = '<i class="bi ' + iconClass + '"></i><span>' + escHtml(message) + '</span>';
        container.appendChild(toast);

        setTimeout(() => {
            toast.style.opacity = '0';
            toast.style.transform = 'translateX(30px)';
            setTimeout(() => toast.remove(), 300);
        }, 4000);
    }

    // ─── AJAX Helpers ─────────────────────────
    function ajaxGet(url, callback) {
        fetch(url)
            .then(r => {
                if (!r.ok) throw new Error('HTTP ' + r.status);
                return r.json();
            })
            .then(callback)
            .catch(e => {
                console.error('AJAX GET error:', url, e);
                callback(null);
            });
    }

    function ajaxPost(url, data, callback) {
        const fd = new FormData();
        Object.entries(data).forEach(([k, v]) => fd.append(k, String(v)));

        fetch(url, { method: 'POST', body: fd })
            .then(r => {
                if (!r.ok) throw new Error('HTTP ' + r.status);
                return r.json();
            })
            .then(callback)
            .catch(e => {
                console.error('AJAX POST error:', url, e);
                callback(null);
            });
    }

    function escHtml(str) {
        if (!str) return '';
        const div = document.createElement('div');
        div.textContent = String(str);
        return div.innerHTML;
    }

})();
