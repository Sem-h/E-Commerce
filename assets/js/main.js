/**
 * V-Commerce - Frontend JavaScript
 */

document.addEventListener('DOMContentLoaded', function () {
    // Hero Slider
    initHeroSlider();
    // Mobile Menu
    initMobileMenu();
    // Quantity Selectors
    initQuantitySelectors();
    // Toast notifications auto-dismiss
    initToasts();
});

// ==================== HERO SLIDER ====================
function initHeroSlider() {
    const slides = document.querySelectorAll('.hero-slide');
    const dots = document.querySelectorAll('.hero-dot');
    if (slides.length === 0) return;

    let current = 0;
    let interval = setInterval(nextSlide, 5000);

    function showSlide(index) {
        slides.forEach(s => s.classList.remove('active'));
        dots.forEach(d => d.classList.remove('active'));
        slides[index].classList.add('active');
        if (dots[index]) dots[index].classList.add('active');
        current = index;
    }

    function nextSlide() {
        showSlide((current + 1) % slides.length);
    }

    dots.forEach((dot, i) => {
        dot.addEventListener('click', () => {
            clearInterval(interval);
            showSlide(i);
            interval = setInterval(nextSlide, 5000);
        });
    });
}

// ==================== MOBILE MENU ====================
function initMobileMenu() {
    const toggle = document.querySelector('.mobile-toggle');
    const nav = document.querySelector('.nav-list');
    if (!toggle || !nav) return;

    toggle.addEventListener('click', () => {
        nav.classList.toggle('active');
        toggle.innerHTML = nav.classList.contains('active') ? '<i class="fas fa-times"></i>' : '<i class="fas fa-bars"></i>';
    });
}

// ==================== CART FUNCTIONS ====================
function addToCart(productId, quantity) {
    quantity = quantity || 1;
    fetch(BASE_URL + '/ajax/cart.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'action=add&product_id=' + productId + '&quantity=' + quantity
    })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                showToast(data.message || 'Ürün sepete eklendi!', 'success');
                updateCartBadge(data.cart_count);
            } else {
                showToast(data.message || 'Hata oluştu!', 'error');
            }
        })
        .catch(() => showToast('Bir hata oluştu!', 'error'));
}

function removeFromCart(cartId) {
    if (!confirm('Ürünü sepetten kaldırmak istediğinize emin misiniz?')) return;
    fetch(BASE_URL + '/ajax/cart.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'action=remove&cart_id=' + cartId
    })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                location.reload();
            }
        });
}

function updateCartQty(cartId, quantity) {
    fetch(BASE_URL + '/ajax/cart.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'action=update&cart_id=' + cartId + '&quantity=' + quantity
    })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                location.reload();
            }
        });
}

function updateCartBadge(count) {
    const badges = document.querySelectorAll('.cart-badge-count');
    badges.forEach(b => {
        b.textContent = count;
        b.style.display = count > 0 ? 'flex' : 'none';
    });
}

// ==================== WISHLIST ====================
function toggleWishlist(productId) {
    fetch(BASE_URL + '/ajax/wishlist.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'product_id=' + productId
    })
        .then(res => res.json())
        .then(data => {
            showToast(data.message, data.success ? 'success' : 'error');
        });
}

// ==================== PRICE ALERT ====================
function togglePriceAlert(productId) {
    fetch(BASE_URL + '/ajax/price-alert.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'action=toggle&product_id=' + productId
    })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                showToast(data.message, 'success');
                // Toggle buton görünümü
                const btn = document.querySelector('.pd-price-alert-btn');
                if (btn) {
                    btn.classList.toggle('active', data.action === 'added');
                    btn.innerHTML = data.action === 'added'
                        ? '<i class="fas fa-bell-slash"></i> Uyarı Aktif'
                        : '<i class="fas fa-bell"></i> Fiyat Düşünce Haber Ver';
                }
            } else {
                showToast(data.message, 'error');
            }
        })
        .catch(() => showToast('Bir hata oluştu!', 'error'));
}

// ==================== QUANTITY SELECTOR ====================
function initQuantitySelectors() {
    document.querySelectorAll('.quantity-selector').forEach(sel => {
        const input = sel.querySelector('input');
        const minus = sel.querySelector('.qty-minus');
        const plus = sel.querySelector('.qty-plus');

        if (minus) minus.addEventListener('click', () => {
            let val = parseInt(input.value) - 1;
            if (val >= 1) input.value = val;
        });
        if (plus) plus.addEventListener('click', () => {
            let val = parseInt(input.value) + 1;
            let max = parseInt(input.getAttribute('max') || 99);
            if (val <= max) input.value = val;
        });
    });
}

// ==================== TOAST ====================
function showToast(message, type) {
    type = type || 'success';
    let container = document.querySelector('.toast-container');
    if (!container) {
        container = document.createElement('div');
        container.className = 'toast-container';
        document.body.appendChild(container);
    }

    const toast = document.createElement('div');
    toast.className = 'toast' + (type === 'error' ? ' error' : '');
    toast.innerHTML = '<i class="fas fa-' + (type === 'success' ? 'check-circle' : 'exclamation-circle') + '"></i>' +
        '<span>' + message + '</span>' +
        '<button class="toast-close" onclick="this.parentElement.remove()">&times;</button>';
    container.appendChild(toast);

    setTimeout(() => { if (toast.parentElement) toast.remove(); }, 4000);
}

function initToasts() {
    document.querySelectorAll('.alert').forEach(alert => {
        setTimeout(() => { if (alert.parentElement) alert.style.opacity = '0'; setTimeout(() => alert.remove(), 300); }, 5000);
    });
}

// BASE_URL global
var BASE_URL = document.querySelector('meta[name="base-url"]')?.content || '/E-Ticaret';
