
import {
    getCart,
    updateQuantity,
    removeFromCart,
    clearCart,
    getCartCount,
    getCartSubtotal,
    estimateShipping
} from './cart.js';

import { formatRupiah } from './api.js';

const elements = {
    badges: document.querySelectorAll('.navbar__cart-badge'),
    empty: document.querySelector('#cart-empty'),
    content: document.querySelector('#cart-content'),
    items: document.querySelector('#cart-items'),
    itemCount: document.querySelector('#cart-item-count'),
    subtotal: document.querySelector('#cart-subtotal'),
    shipping: document.querySelector('#cart-shipping'),
    total: document.querySelector('#cart-total'),
    clearButton: document.querySelector('#clear-cart'),
    checkoutButton: document.querySelector('#checkout-button'),
    notice: document.querySelector('#cart-notice'),
    hamburger: document.querySelector('.navbar__hamburger'),
    mobileMenu: document.querySelector('#mobile-menu')
};

function updateCartBadge() {
    const count = getCartCount();

    elements.badges.forEach((badge) => {
        badge.textContent = count;
        badge.setAttribute('aria-label', `${count} barang di keranjang`);
    });
}

function createElement(tag, className, text) {
    const element = document.createElement(tag);

    if (className) {
        element.className = className;
    }

    if (text !== undefined) {
        element.textContent = text;
    }

    return element;
}

function showNotice(message) {
    elements.notice.textContent = message;
    elements.notice.hidden = false;
}

function renderCartItem(item) {
    const article = createElement('article', 'cart-item');

    const image = createElement('img', 'cart-item__image');
    image.src = item.image;
    image.alt = item.name;
    image.loading = 'lazy';

    image.addEventListener('error', () => {
        image.removeAttribute('src');
        image.alt = 'Gambar produk tidak tersedia';
    }, { once: true });

    const details = createElement('div', 'cart-item__details');

    const link = createElement('a', '', item.name);
    link.href = `product-detail.html?id=${encodeURIComponent(item.product_id)}`;

    const name = createElement('h3', 'cart-item__name');
    name.append(link);

    const price = createElement(
        'p',
        'cart-item__price',
        formatRupiah(item.price)
    );

    const bottom = createElement('div', 'cart-item__bottom');
    const quantityControl = createElement('div', 'quantity-control');

    const decreaseButton = createElement('button', '', '−');
    decreaseButton.type = 'button';
    decreaseButton.dataset.action = 'decrease';
    decreaseButton.dataset.id = item.product_id;
    decreaseButton.disabled = item.quantity <= 1;
    decreaseButton.setAttribute(
        'aria-label',
        `Kurangi jumlah ${item.name}`
    );

    const quantity = createElement('span', '', item.quantity);
    quantity.setAttribute('aria-label', 'Jumlah barang');

    const increaseButton = createElement('button', '', '+');
    increaseButton.type = 'button';
    increaseButton.dataset.action = 'increase';
    increaseButton.dataset.id = item.product_id;
    increaseButton.disabled = item.quantity >= item.max_stock;
    increaseButton.setAttribute(
        'aria-label',
        `Tambah jumlah ${item.name}`
    );

    quantityControl.append(
        decreaseButton,
        quantity,
        increaseButton
    );

    const removeButton = createElement(
        'button',
        'cart-item__remove',
        'Hapus'
    );

    removeButton.type = 'button';
    removeButton.dataset.action = 'remove';
    removeButton.dataset.id = item.product_id;
    removeButton.setAttribute('aria-label', `Hapus ${item.name}`);

    bottom.append(quantityControl, removeButton);
    details.append(name, price, bottom);
    article.append(image, details);

    return article;
}

function renderCart() {
    const cart = getCart();
    const isEmpty = cart.length === 0;

    elements.empty.hidden = !isEmpty;
    elements.content.hidden = isEmpty;

    elements.items.replaceChildren();

    updateCartBadge();

    if (isEmpty) {
        elements.itemCount.textContent = '0';
        elements.subtotal.textContent = formatRupiah(0);
        elements.shipping.textContent = formatRupiah(0);
        elements.total.textContent = formatRupiah(0);
        return;
    }

    const fragment = document.createDocumentFragment();

    cart.forEach((item) => {
        fragment.append(renderCartItem(item));
    });

    elements.items.append(fragment);

    // Jumlah jenis produk, bukan total seluruh unit.
    elements.itemCount.textContent = cart.length;

    const subtotal = getCartSubtotal();
    const shipping = estimateShipping(subtotal);
    const total = subtotal + shipping;

    elements.subtotal.textContent = formatRupiah(subtotal);
    elements.shipping.textContent =
        shipping === 0 && subtotal > 0
            ? 'Gratis'
            : formatRupiah(shipping);

    elements.total.textContent = formatRupiah(total);
}

elements.items.addEventListener('click', (event) => {
    const button = event.target.closest('button[data-action]');

    if (!button) return;

    const productId = Number(button.dataset.id);
    const action = button.dataset.action;
    const cart = getCart();

    const item = cart.find(
        (product) => product.product_id === productId
    );

    if (!item) {
        renderCart();
        return;
    }

    if (action === 'increase') {
        if (item.quantity >= item.max_stock) {
            showNotice(`Stok ${item.name} sudah mencapai batas.`);
            return;
        }

        updateQuantity(productId, item.quantity + 1);
        showNotice(`Jumlah ${item.name} berhasil ditambah.`);
    }

    if (action === 'decrease') {
        if (item.quantity > 1) {
            updateQuantity(productId, item.quantity - 1);
            showNotice(`Jumlah ${item.name} berhasil dikurangi.`);
        }
    }

    if (action === 'remove') {
        removeFromCart(productId);
        showNotice(`${item.name} dihapus dari keranjang.`);
    }

    // renderCart juga dipanggil melalui event cart:updated.
    renderCart();
});

elements.clearButton.addEventListener('click', () => {
    if (getCart().length === 0) return;

    const confirmed = window.confirm(
        'Yakin ingin mengosongkan seluruh keranjang?'
    );

    if (!confirmed) return;

    clearCart();
    showNotice('Keranjang berhasil dikosongkan.');
    renderCart();
});

// Sinkronisasi ketika cart.js memperbarui isi keranjang.
window.addEventListener('cart:updated', renderCart);

// Navigasi mobile.
if (elements.hamburger && elements.mobileMenu) {
    elements.hamburger.addEventListener('click', () => {
        const isOpen = elements.mobileMenu.classList.contains('active');

        elements.mobileMenu.classList.toggle('active', !isOpen);
        elements.hamburger.setAttribute(
            'aria-expanded',
            String(!isOpen)
        );
        elements.hamburger.setAttribute(
            'aria-label',
            isOpen ? 'Buka menu' : 'Tutup menu'
        );
    });

    elements.mobileMenu.querySelectorAll('a').forEach((link) => {
        link.addEventListener('click', () => {
            elements.mobileMenu.classList.remove('active');
            elements.hamburger.setAttribute('aria-expanded', 'false');
            elements.hamburger.setAttribute('aria-label', 'Buka menu');
        });
    });
}

// Tombol checkout belum mengirim pesanan ke database.
// Backend checkout akan dibuat pada tahap berikutnya.
elements.checkoutButton.addEventListener('click', (event) => {
    if (getCart().length === 0) {
        event.preventDefault();
        showNotice('Keranjang masih kosong. Tambahkan produk dahulu.');
    }
});

renderCart();
