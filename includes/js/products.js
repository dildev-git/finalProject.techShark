/**
 * =============================================================================
 * products.js — Master Shared Product Listing Page JavaScript
 * =============================================================================
 * Handles filtering, sorting, price sliders, and AJAX updates for ALL product 
 * pages: Laptops, Desktops, Components, Accessories, Audio, Storage.
 * =============================================================================
 */

// ─── Read page-specific config from data attributes ───────────────────────────
const _section = document.querySelector('.products-listing');
const PAGE_CAT = _section ? (_section.dataset.category || 'products') : 'products';
const PAGE_LABEL = _section ? (_section.dataset.categoryLabel || 'Products') : 'Products';

document.addEventListener('DOMContentLoaded', function () {

    // ── Elements ────────────────────────────────────────────────────────────
    const minPriceSlider = document.querySelector('.range-min');
    const maxPriceSlider = document.querySelector('.range-max');
    const minPriceText = document.querySelector('.price-input[placeholder="Min"]');
    const maxPriceText = document.querySelector('.price-input[placeholder="Max"]');
    const clearFiltersBtn = document.querySelector('.clear-filters');
    const sortSelect = document.getElementById('sort');
    const filterCheckboxes = document.querySelectorAll('.filter-option input[type="checkbox"], .filter-option input[type="radio"]');
    const checkCompatibilityBtn = document.getElementById('check-compatibility');

    // ── Helper: Format Number (e.g. 1000000 → "1,000,000") ──────────────────
    const formatNumber = function (num) {
        const raw = num.toString().replace(/,/g, '') || 0;
        return parseInt(raw).toLocaleString('en-US');
    };

    // ── Price Slider & Text Box Syncing ─────────────────────────────────────
    if (minPriceSlider && maxPriceSlider && minPriceText && maxPriceText) {
        // Text to Slider
        minPriceText.addEventListener('input', function () {
            this.value = formatNumber(this.value);
            minPriceSlider.value = this.value.replace(/,/g, '');
            debounce(refreshProducts, 500)();
        });
        maxPriceText.addEventListener('input', function () {
            this.value = formatNumber(this.value);
            maxPriceSlider.value = this.value.replace(/,/g, '');
            debounce(refreshProducts, 500)();
        });

        // Slider to Text
        minPriceSlider.addEventListener('input', function () {
            minPriceText.value = formatNumber(this.value);
            debounce(refreshProducts, 500)();
        });
        maxPriceSlider.addEventListener('input', function () {
            maxPriceText.value = formatNumber(this.value);
            debounce(refreshProducts, 500)();
        });

        // Mouse-wheel scrolling on price text boxes
        const handleScroll = function (e, input, slider) {
            e.preventDefault();
            let val = parseInt(input.value.replace(/,/g, '')) || 0;
            const step = 1000;
            val += (e.deltaY < 0) ? step : -step;
            val = Math.max(0, Math.min(1000000, val));
            input.value = formatNumber(val);
            slider.value = val;

            clearTimeout(input.scrollTimeout);
            input.scrollTimeout = setTimeout(function () {
                debounce(refreshProducts, 10)();
            }, 50);
        };

        minPriceText.addEventListener('wheel', function (e) { handleScroll(e, minPriceText, minPriceSlider); });
        maxPriceText.addEventListener('wheel', function (e) { handleScroll(e, maxPriceText, maxPriceSlider); });
    }

    // ── Filter Checkboxes ───────────────────────────────────────────────────
    filterCheckboxes.forEach(function (checkbox) {
        checkbox.addEventListener('change', function () {
            debounce(refreshProducts, 300)();
        });
    });

    // ── Clear-All Button ────────────────────────────────────────────────────
    if (clearFiltersBtn) {
        clearFiltersBtn.addEventListener('click', function () {
            filterCheckboxes.forEach(function (cb) { cb.checked = false; });

            if (minPriceText) {
                setTimeout(function () {
                    minPriceText.value = formatNumber(0);
                    maxPriceText.value = formatNumber(1000000);
                    minPriceSlider.value = 0;
                    maxPriceSlider.value = 1000000;
                }, 10);
            }
            refreshProducts();
        });
    }

    // ── Sort Dropdown ───────────────────────────────────────────────────────
    if (sortSelect) {
        sortSelect.addEventListener('change', function () {
            refreshProducts();
        });
    }

    // ── Compatibility Checker (Components Page Only) ────────────────────────
    if (checkCompatibilityBtn) {
        checkCompatibilityBtn.addEventListener('click', checkCompatibility);
    }

    // ── Load initial products ───────────────────────────────────────────────
    refreshProducts();
});

// =============================================================================
// Shared Utility Functions
// =============================================================================

function debounce(func, wait) {
    let timeout;
    return function (...args) {
        const later = function () {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

function refreshProducts() {
    const form = document.getElementById('filter-form');
    const sortSelect = document.getElementById('sort');
    const grid = document.querySelector('.products-grid');
    const infoEl = document.querySelector('.results-info');

    if (!form || !grid) return;

    // Show loading spinner
    grid.innerHTML = `
        <div class="loading-products">
            <div class="loading-spinner"></div>
            <p>Loading ${PAGE_LABEL}...</p>
        </div>`;

    const params = new URLSearchParams();
    const formData = new FormData(form);

    // ALL Array keys from both old JS files combined!
    const ARRAY_KEYS = [
        'brand[]', 'category[]', 'compatibility[]', 'performance[]',
        'Usetype[]', 'storage[]', 'processor[]', 'ram[]', 'gpu[]', 'usage[]', 'screen[]'
    ];

    for (const [key, value] of formData) {
        if (ARRAY_KEYS.includes(key)) {
            if (!params.getAll(key).includes(value)) {
                params.append(key, value);
            }
        } else {
            params.set(key, value);
        }
    }

    if (sortSelect) params.set('sort', sortSelect.value);

    // Tell the server which product category to query using the smart PAGE_CAT
    params.set('category', PAGE_CAT);

    fetch('ajax_get_products.php?' + params.toString(), {
        method: 'GET',
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
        .then(function (response) {
            if (!response.ok) throw new Error('Network response was not ok');
            return response.json();
        })
        .then(function (data) {
            if (data.success) {
                grid.innerHTML = data.html;

                if (infoEl) {
                    infoEl.innerHTML = `<p>Showing <strong>${data.total}</strong> of <strong>${data.total}</strong> ${PAGE_LABEL}</p>`;
                }

                // Update the browser URL bar smoothly
                const newUrl = window.location.pathname + '?' + params.toString();
                window.history.pushState({ path: newUrl }, '', newUrl);
            } else {
                throw new Error(data.message || 'Failed to load ' + PAGE_LABEL);
            }
        })
        .catch(function (error) {
            console.error('refreshProducts error:', error);
            grid.innerHTML = `
            <div class="error-loading">
                <i class="fas fa-exclamation-triangle"></i>
                <p>Error loading ${PAGE_LABEL}. Please try again.</p>
            </div>`;
        });
}

function checkCompatibility() {
    const cpuSelect = document.getElementById('cpu-select');
    const motherboardSelect = document.getElementById('motherboard-select');

    if (!cpuSelect || !motherboardSelect) return;

    if (!cpuSelect.value || !motherboardSelect.value) {
        showNotification('Please select both CPU and Motherboard', 'warning');
        return;
    }

    const cpu = cpuSelect.value.toLowerCase();
    const motherboard = motherboardSelect.value.toLowerCase();
    let compatible = false;
    let message = '';

    if (cpu.includes('amd') && motherboard.includes('am5')) {
        compatible = true;
        message = 'Compatible! AMD CPU works with AM5 motherboard.';
    } else if (cpu.includes('intel') && motherboard.includes('z790')) {
        compatible = true;
        message = 'Compatible! Intel CPU works with Z790 motherboard.';
    } else {
        message = 'Components may not be compatible. Check specifications.';
    }

    // Note: Make sure you have a showNotification function available, or replace this with alert()
    if (typeof showNotification === 'function') {
        showNotification(message, compatible ? 'success' : 'warning');
    } else {
        alert(message);
    }
}