
import { getProducts, formatRupiah } from './api.js';
import { getCartCount, addToCart } from './cart.js';

const content = document.querySelector('#detail-content');
const cartBadges = document.querySelectorAll('.navbar__cart-badge');

function updateCartBadge() {
    const count = getCartCount();

    cartBadges.forEach(badge => {
        badge.textContent = String(count);
        badge.setAttribute('aria-label', `${count} item di keranjang`);
    });
}

function makeElement(tag, className, text) {
    const element = document.createElement(tag);

    if (className) element.className = className;
    if (text !== undefined) element.textContent = text;

    return element;
}

function showError(message) {
    content.className = 'detail-loading';
    content.replaceChildren();

    const heading = makeElement('h2', '', 'Produk tidak ditemukan');
    const description = makeElement('p', '', message);
    const link = makeElement('a', '', 'Kembali ke katalog');

    link.href = 'products.html';

    content.append(heading, description, link);
}

function renderProduct(product) {
    content.className = '';
    content.replaceChildren();

    const layout = makeElement('section', 'detail-layout');

    // Bagian gambar
    const imageWrap = makeElement('div', 'detail-image-wrap');
    const image = makeElement('img', 'detail-image');

    image.src = product.image;
    image.alt = product.name;

    image.addEventListener('error', () => {
        image.alt = 'Gambar produk tidak tersedia';
        image.style.display = 'none';

        const fallback = makeElement(
            'p',
            '',
            'Gambar produk tidak tersedia.'
        );

        fallback.style.padding = '24px';
        imageWrap.append(fallback);
    }, { once: true });

    imageWrap.append(image);

    // Bagian informasi
    const info = makeElement('div', 'detail-info');

    const category = makeElement(
        'p',
        'detail-category',
        product.category || 'Tanpa kategori'
    );

    const title = makeElement('h1', 'detail-title', product.name);

    const price = makeElement(
        'p',
        'detail-price',
        formatRupiah(product.price)
    );

    const stock = makeElement(
        'p',
        'detail-stock',
        Number(product.stock) > 0
            ? `Stok tersedia: ${product.stock}`
            : 'Stok habis'
    );

    const descriptionTitle = makeElement('h2', '', 'Deskripsi Produk');
    const description = makeElement(
        'p',
        'detail-description',
        product.description || 'Belum ada deskripsi produk.'
    );

    const quantityLabel = makeElement('label', '', 'Jumlah');
    quantityLabel.htmlFor = 'detail-quantity';

    const quantityWrap = makeElement('div', 'detail-quantity');

    const decreaseButton = makeElement('button', '', '−');
    decreaseButton.type = 'button';
    decreaseButton.setAttribute('aria-label', 'Kurangi jumlah');

    const quantityInput = makeElement('input');
    quantityInput.id = 'detail-quantity';
    quantityInput.type = 'number';
    quantityInput.min = '1';
    quantityInput.max = String(product.stock);
    quantityInput.step = '1';
    quantityInput.value = '1';
    quantityInput.inputMode = 'numeric';

    const increaseButton = makeElement('button', '', '+');
    increaseButton.type = 'button';
    increaseButton.setAttribute('aria-label', 'Tambah jumlah');

    const addButton = makeElement(
        'button',
        'detail-add-button',
        'Tambah ke Keranjang'
    );

    addButton.type = 'button';
    addButton.disabled = Number(product.stock) <= 0;

    const message = makeElement('p', 'detail-message');
    message.setAttribute('role', 'status');
    message.setAttribute('aria-live', 'polite');

    function getQuantity() {
        const parsed = Number.parseInt(quantityInput.value, 10);

        if (!Number.isFinite(parsed)) return 1;

        return Math.min(
            Math.max(parsed, 1),
            Number(product.stock)
        );
    }

    function normalizeQuantity() {
        if (Number(product.stock) <= 0) {
            quantityInput.value = '0';
            return;
        }

        quantityInput.value = String(getQuantity());
    }

    decreaseButton.addEventListener('click', () => {
        quantityInput.value = String(getQuantity() - 1);
        normalizeQuantity();
    });

    increaseButton.addEventListener('click', () => {
        quantityInput.value = String(getQuantity() + 1);
        normalizeQuantity();
    });

    quantityInput.addEventListener('change', normalizeQuantity);

    addButton.addEventListener('click', () => {
        normalizeQuantity();

        if (Number(product.stock) <= 0) {
            message.textContent = 'Maaf, stok produk sedang habis.';
            return;
        }

        const result = addToCart(product, getQuantity());

        message.textContent = result.message;
        message.style.color = result.success ? 'green' : '#b91c1c';

        if (result.success) {
            updateCartBadge();
        }
    });

    quantityWrap.append(
        decreaseButton,
        quantityInput,
        increaseButton
    );

    info.append(
        category,
        title,
        price,
        stock,
        descriptionTitle,
        description,
        quantityLabel,
        quantityWrap,
        addButton,
        message
    );

    layout.append(imageWrap, info);
    content.append(layout);

    document.title = `${product.name} — Pasar Gaib`;
}

async function initProductDetail() {
    updateCartBadge();

    window.addEventListener('cart:updated', updateCartBadge);
    window.addEventListener('storage', updateCartBadge);

    const hamburger = document.querySelector('.navbar__hamburger');
    const mobileMenu = document.querySelector('#mobile-menu');

    hamburger?.addEventListener('click', () => {
        const isOpen =
            hamburger.getAttribute('aria-expanded') === 'true';

        hamburger.setAttribute('aria-expanded', String(!isOpen));
        hamburger.setAttribute(
            'aria-label',
            isOpen ? 'Buka menu' : 'Tutup menu'
        );

        mobileMenu?.classList.toggle('active', !isOpen);
    });

    mobileMenu?.querySelectorAll('a').forEach(link => {
        link.addEventListener('click', () => {
            mobileMenu.classList.remove('active');
            hamburger?.setAttribute('aria-expanded', 'false');
            hamburger?.setAttribute('aria-label', 'Buka menu');
        });
    });

    const productId = Number(
        new URLSearchParams(window.location.search).get('id')
    );

    if (!Number.isInteger(productId) || productId <= 0) {
        showError('ID produk pada URL tidak valid.');
        return;
    }

    try {
        const result = await getProducts();

        if (!result.success) {
            throw new Error(
                result.message || 'Gagal mengambil data produk.'
            );
        }

        const product = result.data.find(
            item => Number(item.id) === productId
        );

        if (!product) {
            showError('Produk tidak tersedia atau sudah tidak aktif.');
            return;
        }

        renderProduct(product);
    } catch (error) {
        console.error('Gagal memuat detail produk:', error);

        showError(
            'Detail produk gagal dimuat. Periksa koneksi API, lalu coba lagi.'
        );
    }
}

document.addEventListener('DOMContentLoaded', initProductDetail);
