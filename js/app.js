/**
 * CampusCare - Main JavaScript
 * Common utilities, SweetAlert2 wrappers, form validation, sidebar toggle
 */

// ============================================================
// Sidebar Toggle (Mobile)
// ============================================================
document.addEventListener('DOMContentLoaded', function () {
    const sidebarToggle = document.getElementById('sidebarToggle');
    const sidebar = document.getElementById('sidebar');
    const overlay = document.getElementById('sidebarOverlay');

    if (sidebarToggle && sidebar) {
        sidebarToggle.addEventListener('click', function () {
            sidebar.classList.toggle('show');
            if (overlay) overlay.classList.toggle('show');
        });
    }

    if (overlay) {
        overlay.addEventListener('click', function () {
            sidebar.classList.remove('show');
            overlay.classList.remove('show');
        });
    }

    // Logout confirmation via SweetAlert2
    const logoutLink = document.getElementById('logoutLink');
    if (logoutLink) {
        logoutLink.addEventListener('click', function (e) {
            e.preventDefault();
            const href = this.getAttribute('href');
            Swal.fire({
                title: 'Log out?',
                text: 'Are you sure you want to log out?',
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#0d6e3f',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Yes, log out',
                cancelButtonText: 'Cancel',
                reverseButtons: true
            }).then(function (result) {
                if (result.isConfirmed) {
                    window.location.href = href;
                }
            });
        });
    }

    // Show any toast that was scheduled before a page reload
    const pendingToast = sessionStorage.getItem('pendingToast');
    if (pendingToast) {
        sessionStorage.removeItem('pendingToast');
        const { icon, title } = JSON.parse(pendingToast);
        showToast(icon, title);
    }

    // Auto-dismiss alerts after page load
    initFormValidation();
});

// ============================================================
// SweetAlert2 Helpers
// ============================================================

/**
 * Show a SweetAlert2 alert
 */
function showAlert(icon, title, text) {
    return Swal.fire({
        icon: icon,
        title: title,
        text: text,
        confirmButtonColor: '#0d6e3f',
        customClass: {
            popup: 'swal-custom-popup'
        }
    });
}

/**
 * Show a toast notification (top-right, auto-dismiss)
 */
function showToast(icon, title) {
    const Toast = Swal.mixin({
        toast: true,
        position: 'top-end',
        showConfirmButton: false,
        timer: 3000,
        timerProgressBar: true,
        didOpen: (toast) => {
            toast.addEventListener('mouseenter', Swal.stopTimer);
            toast.addEventListener('mouseleave', Swal.resumeTimer);
        }
    });
    return Toast.fire({ icon: icon, title: title });
}

/**
 * Schedule a toast to show after the next page reload.
 * Saves the toast data in sessionStorage, then immediately reloads.
 */
function scheduleToast(icon, title) {
    sessionStorage.setItem('pendingToast', JSON.stringify({ icon, title }));
    location.reload();
}

/**
 * Show a confirmation dialog
 */
function showConfirm(title, text, confirmText, icon) {
    return Swal.fire({
        title: title || 'Are you sure?',
        html: text || 'This action cannot be undone.',
        icon: icon || 'warning',
        showCancelButton: true,
        confirmButtonColor: '#0d6e3f',
        cancelButtonColor: '#6c757d',
        confirmButtonText: confirmText || 'Yes, proceed',
        cancelButtonText: 'Cancel',
        reverseButtons: true
    });
}

/**
 * Show a delete confirmation dialog
 */
function showDeleteConfirm(itemName) {
    return Swal.fire({
        title: 'Delete ' + (itemName || 'this item') + '?',
        text: 'This action cannot be undone.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#c0392b',
        cancelButtonColor: '#6c757d',
        confirmButtonText: '<i class="bi bi-trash me-1"></i> Delete',
        cancelButtonText: 'Cancel',
        reverseButtons: true
    });
}

// ============================================================
// Form Validation
// ============================================================

/**
 * Initialize Bootstrap form validation on all forms with .needs-validation class
 */
function initFormValidation() {
    const forms = document.querySelectorAll('.needs-validation');
    forms.forEach(function (form) {
        form.addEventListener('submit', function (event) {
            if (!form.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();

                // Find first invalid field and focus it
                const firstInvalid = form.querySelector(':invalid');
                if (firstInvalid) {
                    firstInvalid.focus();
                    showToast('error', 'Please fill in all required fields.');
                }
            }
            form.classList.add('was-validated');
        }, false);
    });
}

// ============================================================
// Dynamic Search (Debounced)
// ============================================================

/**
 * Debounce function to limit the rate of function calls
 */
function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

/**
 * Initialize live search on an input field
 * @param {string} inputSelector - CSS selector for search input
 * @param {string} targetUrl - AJAX endpoint URL
 * @param {string} resultContainerId - ID of container to populate with results
 */
function initLiveSearch(inputSelector, targetUrl, resultContainerId) {
    const input = document.querySelector(inputSelector);
    if (!input) return;

    const searchHandler = debounce(function (e) {
        const query = e.target.value.trim();
        if (query.length < 2) return;

        fetch(targetUrl + '?search=' + encodeURIComponent(query), {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
            .then(response => response.text())
            .then(html => {
                const container = document.getElementById(resultContainerId);
                if (container) container.innerHTML = html;
            })
            .catch(err => console.error('Search error:', err));
    }, 300);

    input.addEventListener('input', searchHandler);
}

// ============================================================
// AJAX Helpers
// ============================================================

/**
 * Send a POST request with form data
 */
function postForm(url, formData) {
    return fetch(url, {
        method: 'POST',
        body: formData,
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    }).then(response => response.json());
}

/**
 * Send a POST request with JSON data
 */
function postJson(url, data) {
    return fetch(url, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: JSON.stringify(data)
    }).then(response => response.json());
}

// ============================================================
// Utility Functions
// ============================================================

/**
 * Format a number with commas
 */
function formatNumber(num) {
    return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ',');
}

/**
 * Copy text to clipboard
 */
function copyToClipboard(text) {
    navigator.clipboard.writeText(text).then(() => {
        showToast('success', 'Copied to clipboard!');
    });
}

/**
 * Print a specific section of the page
 */
function printSection(elementId) {
    const element = document.getElementById(elementId);
    if (!element) return;

    const printWindow = window.open('', '_blank');
    printWindow.document.write('<html><head><title>Print</title>');
    printWindow.document.write('<link rel="stylesheet" href="' + document.querySelector('link[href*="bootstrap.min.css"]').href + '">');
    printWindow.document.write('</head><body class="p-4">');
    printWindow.document.write(element.innerHTML);
    printWindow.document.write('</body></html>');
    printWindow.document.close();
    printWindow.print();
}
