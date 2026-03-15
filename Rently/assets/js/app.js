/**
 * Rently — Client-Side JavaScript
 * Handles: Search/Filter, Booking (AJAX collision check + pricing), Reviews, Dashboard.
 */
(function () {
    'use strict';

    const BASE_URL = window.location.origin + '/Rently/';

    // ─── Utility ────────────────────────────────────────────
    function showAlert(container, message, type = 'danger') {
        container.innerHTML = `
            <div class="alert alert-${type} alert-dismissible fade show" role="alert">
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>`;
        container.classList.remove('d-none');
    }

    // ═══════════════════════════════════════════════════════
    // 1. SEARCH & FILTER (Home Page)
    // ═══════════════════════════════════════════════════════
    const searchForm = document.getElementById('search-form');
    if (searchForm) {
        searchForm.addEventListener('submit', async function (e) {
            e.preventDefault();

            const category  = document.getElementById('filter-category').value;
            const location  = document.getElementById('filter-location').value;
            const startDate = document.getElementById('filter-start').value;
            const endDate   = document.getElementById('filter-end').value;

            const params = new URLSearchParams();
            if (category) params.append('category', category);
            if (location) params.append('location', location);
            if (startDate) params.append('start_date', startDate);
            if (endDate) params.append('end_date', endDate);

            try {
                const res  = await fetch(`${BASE_URL}index.php?page=search&${params.toString()}`);
                const data = await res.json();
                renderAssets(data.assets || []);
            } catch (err) {
                console.error('Search error:', err);
            }
        });
    }

    function renderAssets(assets) {
        const grid = document.getElementById('assets-grid');
        const countBadge = document.getElementById('results-count');
        if (!grid) return;

        countBadge.textContent = `${assets.length} listings`;

        if (assets.length === 0) {
            grid.innerHTML = `
                <div class="col-12 text-center py-5">
                    <i class="bi bi-search display-3 text-muted d-block mb-3"></i>
                    <p class="text-muted">No results found. Try adjusting your filters.</p>
                </div>`;
            return;
        }

        const icons = { apartment: 'bi-building', car: 'bi-car-front', sport_venue: 'bi-trophy' };

        grid.innerHTML = assets.map(asset => {
            const isHourly = asset.category === 'sport_venue';
            const price = isHourly ? asset.price_per_hour : asset.price_per_day;
            const unit  = isHourly ? '/hour' : '/day';
            const icon  = icons[asset.category] || 'bi-image';
            const catLabel = asset.category.replace('_', ' ');

            return `
                <div class="col-lg-4 col-md-6">
                    <div class="asset-card card h-100 border-0 shadow-sm">
                        <div class="asset-card-img-wrapper">
                            ${asset.image_url
                                ? `<img src="${BASE_URL}${asset.image_url}" class="card-img-top" alt="${asset.title}">`
                                : `<div class="card-img-top placeholder-img d-flex align-items-center justify-content-center">
                                       <i class="bi ${icon} display-3 text-muted"></i>
                                   </div>`
                            }
                            <span class="category-badge badge">${catLabel.charAt(0).toUpperCase() + catLabel.slice(1)}</span>
                        </div>
                        <div class="card-body d-flex flex-column">
                            <h5 class="card-title fw-bold">${asset.title}</h5>
                            <p class="text-muted small mb-2">
                                <i class="bi bi-geo-alt me-1"></i>${asset.address || 'Location not specified'}
                            </p>
                            <p class="text-muted small mb-3">
                                <i class="bi bi-person me-1"></i>Hosted by ${asset.owner_name}
                            </p>
                            <div class="mt-auto d-flex justify-content-between align-items-center">
                                <div class="price-tag">
                                    <strong class="text-accent fs-5">$${parseFloat(price).toFixed(2)}</strong>
                                    <small class="text-muted">${unit}</small>
                                </div>
                                <a href="${BASE_URL}index.php?page=asset&action=detail&id=${asset.asset_id}"
                                   class="btn btn-sm btn-outline-accent">
                                    View Details <i class="bi bi-arrow-right ms-1"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>`;
        }).join('');
    }

    // ═══════════════════════════════════════════════════════
    // 2. BOOKING: Availability Check + Dynamic Pricing
    // ═══════════════════════════════════════════════════════
    const bookingForm = document.getElementById('booking-form');
    if (bookingForm) {
        const assetId      = bookingForm.dataset.assetId;
        const category     = bookingForm.dataset.category;
        const pricePerHour = parseFloat(document.getElementById('price-per-hour')?.value || 0);
        const pricePerDay  = parseFloat(document.getElementById('price-per-day')?.value || 0);
        const startInput   = document.getElementById('book-start');
        const endInput     = document.getElementById('book-end');
        const previewDiv   = document.getElementById('price-preview');
        const previewPrice = document.getElementById('preview-price');
        const previewBreak = document.getElementById('preview-breakdown');
        const statusDiv    = document.getElementById('availability-status');
        const checkBtn     = document.getElementById('check-availability-btn');
        const bookBtn      = document.getElementById('book-now-btn');

        // Dynamic price calculation on date change
        function updatePricePreview() {
            const start = new Date(startInput.value);
            const end   = new Date(endInput.value);

            if (!startInput.value || !endInput.value || end <= start) {
                previewDiv.classList.add('d-none');
                return;
            }

            let total = 0, breakdown = '';

            if (category === 'sport_venue') {
                const hours = Math.ceil((end - start) / (1000 * 60 * 60));
                total = hours * pricePerHour;
                breakdown = `${hours} hour${hours > 1 ? 's' : ''} × $${pricePerHour.toFixed(2)}`;
            } else {
                const days = Math.max(Math.ceil((end - start) / (1000 * 60 * 60 * 24)), 1);
                total = days * pricePerDay;
                breakdown = `${days} day${days > 1 ? 's' : ''} × $${pricePerDay.toFixed(2)}`;
            }

            previewPrice.textContent = `$${total.toFixed(2)}`;
            previewBreak.textContent = breakdown;
            previewDiv.classList.remove('d-none');
        }

        startInput.addEventListener('change', updatePricePreview);
        endInput.addEventListener('change', updatePricePreview);

        // Check Availability (AJAX)
        checkBtn.addEventListener('click', async function () {
            if (!startInput.value || !endInput.value) {
                showAlert(statusDiv, 'Please select both start and end dates.');
                return;
            }

            checkBtn.disabled = true;
            checkBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Checking...';

            try {
                const params = new URLSearchParams({
                    asset_id: assetId,
                    start: startInput.value,
                    end: endInput.value
                });
                const res  = await fetch(`${BASE_URL}index.php?page=booking&action=check&${params}`);
                const data = await res.json();

                statusDiv.classList.remove('d-none');

                if (data.available) {
                    statusDiv.innerHTML = `
                        <div class="alert alert-success mb-0">
                            <i class="bi bi-check-circle me-2"></i>${data.message}
                        </div>`;
                    bookBtn.disabled = false;
                } else {
                    statusDiv.innerHTML = `
                        <div class="alert alert-danger mb-0">
                            <i class="bi bi-x-circle me-2"></i>${data.message}
                        </div>`;
                    bookBtn.disabled = true;
                }
            } catch (err) {
                showAlert(statusDiv, 'Error checking availability. Try again.');
            }

            checkBtn.disabled = false;
            checkBtn.innerHTML = '<i class="bi bi-calendar-check me-1"></i>Check Availability';
        });

        // Submit Booking (AJAX)
        bookingForm.addEventListener('submit', async function (e) {
            e.preventDefault();

            bookBtn.disabled = true;
            bookBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Processing...';

            const formData = new FormData(bookingForm);

            try {
                const res  = await fetch(`${BASE_URL}index.php?page=booking&action=create`, {
                    method: 'POST',
                    body: formData
                });
                const data = await res.json();

                if (data.success) {
                    statusDiv.classList.remove('d-none');
                    statusDiv.innerHTML = `
                        <div class="alert alert-success">
                            <i class="bi bi-check-circle me-2"></i>${data.message}
                            <br><small>Booking ID: #${data.booking_id}</small>
                        </div>`;
                    bookBtn.innerHTML = '<i class="bi bi-check-lg me-2"></i>Booked!';
                } else {
                    showAlert(statusDiv, data.error || 'Booking failed.');
                    bookBtn.disabled = false;
                    bookBtn.innerHTML = '<i class="bi bi-credit-card me-2"></i>Book & Pay Now';
                }
            } catch (err) {
                showAlert(statusDiv, 'Network error. Please try again.');
                bookBtn.disabled = false;
                bookBtn.innerHTML = '<i class="bi bi-credit-card me-2"></i>Book & Pay Now';
            }
        });
    }

    // ═══════════════════════════════════════════════════════
    // 3. STAR RATING (Reviews)
    // ═══════════════════════════════════════════════════════
    const starContainer = document.getElementById('star-rating');
    if (starContainer) {
        const stars      = starContainer.querySelectorAll('i');
        const ratingInput = document.getElementById('rating-input');

        stars.forEach(star => {
            star.addEventListener('click', function () {
                const rating = parseInt(this.dataset.rating);
                ratingInput.value = rating;
                stars.forEach((s, i) => {
                    s.classList.toggle('active', i < rating);
                    s.classList.toggle('bi-star-fill', i < rating);
                    s.classList.toggle('bi-star', i >= rating);
                });
            });

            star.addEventListener('mouseenter', function () {
                const rating = parseInt(this.dataset.rating);
                stars.forEach((s, i) => {
                    s.classList.toggle('bi-star-fill', i < rating);
                    s.classList.toggle('bi-star', i >= rating);
                });
            });
        });

        starContainer.addEventListener('mouseleave', function () {
            const current = parseInt(ratingInput.value);
            stars.forEach((s, i) => {
                s.classList.toggle('active', i < current);
                s.classList.toggle('bi-star-fill', i < current);
                s.classList.toggle('bi-star', i >= current);
            });
        });
    }

    // Submit Review (AJAX)
    const reviewForm = document.getElementById('review-form');
    if (reviewForm) {
        reviewForm.addEventListener('submit', async function (e) {
            e.preventDefault();

            const formData = new FormData(reviewForm);
            formData.append('asset_id', reviewForm.dataset.assetId);

            try {
                const res  = await fetch(`${BASE_URL}index.php?page=review&action=add`, {
                    method: 'POST',
                    body: formData
                });
                const data = await res.json();

                if (data.success) {
                    // Append the new review to the DOM
                    const section = document.getElementById('reviews-section');
                    const hr = section.querySelector('hr');
                    const review = data.review;

                    const reviewHtml = `
                        <div class="review-item mb-3 p-3 rounded-3">
                            <div class="d-flex justify-content-between">
                                <strong>${review.full_name}</strong>
                                <span>${'★'.repeat(review.rating)}${'☆'.repeat(5 - review.rating)}</span>
                            </div>
                            <p class="text-muted mb-0 mt-1 small">${review.comment}</p>
                        </div>`;

                    hr.insertAdjacentHTML('beforebegin', reviewHtml);
                    reviewForm.reset();

                    // Reset stars
                    document.getElementById('rating-input').value = 0;
                    document.querySelectorAll('#star-rating i').forEach(s => {
                        s.classList.remove('active', 'bi-star-fill');
                        s.classList.add('bi-star');
                    });
                } else {
                    alert(data.error || 'Failed to submit review.');
                }
            } catch (err) {
                alert('Network error. Try again.');
            }
        });
    }

    // ═══════════════════════════════════════════════════════
    // 4. EDIT ASSET MODAL (Dashboard)
    // ═══════════════════════════════════════════════════════
    document.querySelectorAll('.edit-asset-btn').forEach(btn => {
        btn.addEventListener('click', function () {
            const asset = JSON.parse(this.dataset.asset);
            document.getElementById('edit-asset-id').value     = asset.asset_id;
            document.getElementById('edit-title').value        = asset.title;
            document.getElementById('edit-description').value  = asset.description || '';
            document.getElementById('edit-category').value     = asset.category;
            document.getElementById('edit-address').value      = asset.address || '';
            document.getElementById('edit-status').value       = asset.status;
            document.getElementById('edit-price-hour').value   = asset.price_per_hour;
            document.getElementById('edit-price-day').value    = asset.price_per_day;
        });
    });

})();
