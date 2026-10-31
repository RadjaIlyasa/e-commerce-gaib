import {
    getCart,
    getCartCount,
    getCartSubtotal,
    estimateShipping,
    clearCart
} from './cart.js';

import { formatRupiah, createOrder } from './api.js';

const elements = {
    badges: document.querySelectorAll('.navbar__cart-badge'),
    form: document.querySelector('#checkout-form'),
    layout: document.querySelector('#checkout-layout'),
    empty: document.querySelector('#checkout-empty'),
    products: document.querySelector('#checkout-products'),
    subtotal: document.querySelector('#checkout-subtotal'),
    shipping: document.querySelector('#checkout-shipping'),
    total: document.querySelector('#checkout-total'),
    message: document.querySelector('#checkout-message'),
    submit: document.querySelector('#checkout-submit'),
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

function showMessage(message, type = 'error') {
    elements.message.textContent = message;
    elements.message.hidden = false;
    elements.message.style.background =
        type === 'success' ? '#dff3df' : '#f8dddd';
    elements.message.style.color =
        type === 'success' ? '#205c2a' : '#8b2525';
}

function createElement(tag, className, text) {
    const element = document.createElement(tag);

    if (className) element.className = className;
    if (text !== undefined) element.textContent = text;

    return element;
}

function renderSummary() {
    const cart = getCart();
    const isEmpty = cart.length === 0;

    elements.empty.hidden = !isEmpty;
    elements.layout.hidden = isEmpty;

    updateCartBadge();

    if (isEmpty) return;

    const fragment = document.createDocumentFragment();

    cart.forEach((item) => {
        const row = createElement('div', 'checkout-product');

        const image = createElement('img');
        image.src = item.image;
        image.alt = item.name;
        image.loading = 'lazy';

        image.addEventListener(
            'error',
            () => {
                image.removeAttribute('src');
                image.alt = 'Gambar produk tidak tersedia';
            },
            { once: true }
        );

        const details = createElement('div');
        const name = createElement('h3', 'checkout-product__name', item.name);
        const quantity = createElement(
            'p',
            'checkout-product__quantity',
            `Jumlah: ${item.quantity}`
        );

        details.append(name, quantity);

        const price = createElement(
            'span',
            'checkout-product__price',
            formatRupiah(item.price * item.quantity)
        );

        row.append(image, details, price);
        fragment.append(row);
    });

    elements.products.replaceChildren(fragment);

    const subtotal = getCartSubtotal();
    const shipping = estimateShipping(subtotal);

    elements.subtotal.textContent = formatRupiah(subtotal);
    elements.shipping.textContent =
        shipping === 0 && subtotal > 0 ? 'Gratis' : formatRupiah(shipping);

    elements.total.textContent = formatRupiah(subtotal + shipping);
}

elements.form.addEventListener('submit', async (event) => {
    event.preventDefault();

    const cart = getCart();

    if (cart.length === 0) {
        showMessage('Keranjang kosong. Tambahkan produk sebelum checkout.');
        renderSummary();
        return;
    }

    if (!elements.form.reportValidity()) return;

    const formData = new FormData(elements.form);

    const customer = {
        nama: String(formData.get('nama') || '').trim(),
        email: String(formData.get('email') || '').trim(),
        telepon: String(formData.get('telepon') || '').trim(),
        alamat: String(formData.get('alamat') || '').trim(),
        catatan: String(formData.get('catatan') || '').trim()
    };

    if (
        !customer.nama ||
        !customer.email ||
        !customer.telepon ||
        !customer.alamat
    ) {
        showMessage('Lengkapi semua informasi penerima yang wajib diisi.');
        return;
    }

    // Kirim ID produk dan jumlah saja.
    // Harga serta total harus dihitung ulang dari database oleh PHP.
    const orderData = {
        customer,
        items: cart.map((item) => ({
            product_id: Number(item.product_id),
            quantity: Number(item.quantity)
        })),
        payment_method: formData.get('payment_method') || 'COD'
    };

    elements.submit.disabled = true;
    elements.submit.textContent = 'Memproses Pesanan...';
    elements.message.hidden = true;

    try {
        const result = await createOrder(orderData);

        if (!result || result.success !== true) {
            throw new Error(
                result?.message || 'Pesanan belum berhasil dibuat.'
            );
        }

        const orderNumber =
            result.data?.order_number ??
            result.data?.id_pesanan ??
            result.data?.id ??
            '';

        // Keranjang dikosongkan setelah server mengonfirmasi pesanan.
        clearCart();

        // Ambil metode pembayaran dari respons server.
        const paymentMethod = String(
            result.data?.payment_method || orderData.payment_method
        ).toUpperCase();

        // Pastikan nomor pesanan tersedia.
        if (!orderNumber) {
            throw new Error(
                'Pesanan berhasil dibuat, tetapi nomor pesanan tidak ditemukan.'
            );
        }

        // Arahkan pembeli sesuai metode pembayaran.
        const encodedOrderNumber = encodeURIComponent(orderNumber);

        if (paymentMethod === 'QRIS') {
            window.location.href = `qris.html?kode=${encodedOrderNumber}`;
        } else if (paymentMethod === 'TRANSFER') {
            window.location.href = `transfer.html?kode=${encodedOrderNumber}`;
        } else {
            window.location.href = `order-success.html?kode=${encodedOrderNumber}`;
        }
    } catch (error) {
        showMessage(
            error.message ||
            'Terjadi kesalahan saat mengirim pesanan. Silakan coba lagi.'
        );
    } finally {
        elements.submit.disabled = false;
        elements.submit.textContent = 'Buat Pesanan';
        updateCartBadge();
    }
});

window.addEventListener('cart:updated', renderSummary);

if (elements.hamburger && elements.mobileMenu) {
    elements.hamburger.addEventListener('click', () => {
        const isOpen = elements.mobileMenu.classList.contains('active');

        elements.mobileMenu.classList.toggle('active', !isOpen);
        elements.hamburger.setAttribute('aria-expanded', String(!isOpen));
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

renderSummary();
