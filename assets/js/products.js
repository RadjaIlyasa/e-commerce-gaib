
import { getProducts, getCategories, formatRupiah } from './api.js';
import { getCartCount, addToCart } from './cart.js';

const elements = {
    form: document.querySelector('#catalog-filters'),
    search: document.querySelector('#search-input'),
    category: document.querySelector('#category-filter'),
    minPrice: document.querySelector('#min-price'),
    maxPrice: document.querySelector('#max-price'),
    sort: document.querySelector('#sort-filter'),
    reset: document.querySelector('#reset-filters'),
    count: document.querySelector('#catalog-count'),
    grid: document.querySelector('#catalog-grid'),
    cartBadges: document.querySelectorAll('.navbar__cart-badge')
};

let allProducts = [];
let debounceTimer;

function showMessage(message, isError = false) {
    let notice = document.querySelector('#catalog-notice');

    if (!notice) {
        notice = document.createElement('p');
        notice.id = 'catalog-notice';
        notice.setAttribute('role', 'status');

        elements.grid?.before(notice);
    }

    notice.textContent = message;
    notice.style.color = isError ? '#b91c1c' : 'inherit';
}

function updateCartBadge() {
  const count   = getCartCount();
  const badges  = document.querySelectorAll('.navbar__cart-badge');
  badges.forEach(badge => {
    badge.textContent = count > 99 ? '99+' : String(count);
    badge.classList.toggle('visible', count > 0);
  });
}

function createElement(tag, className, text) {
    const element = document.createElement(tag);

    if (className) element.className = className;
    if (text !== undefined) element.textContent = text;

    return element;
}

function createProductCard(product) {
    const card = createElement('article', 'product-card');

    const imageWrap = createElement('div', 'product-card__image-wrap');
    const link = createElement('a');

    link.href = `product-detail.html?id=${encodeURIComponent(product.id)}`;
    link.setAttribute('aria-label', `Lihat detail ${product.name}`);

    const image = document.createElement('img');
    image.src = product.image;
    image.alt = product.name;
    image.loading = 'lazy';

    image.addEventListener('error', () => {
        image.alt = `Gambar ${product.name} tidak tersedia`;
        image.style.display = 'none';
    }, { once: true });

    link.append(image);
    imageWrap.append(link);

    const body = createElement('div', 'product-card__body');
    const category = createElement(
        'p',
        'product-card__category',
        product.category || 'Tanpa kategori'
    );

    const name = createElement('h3', 'product-card__name');
    const nameLink = createElement('a', '', product.name);
    nameLink.href = link.href;
    name.append(nameLink);

    const description = createElement(
        'p',
        'product-card__desc',
        product.description || 'Tidak ada deskripsi produk.'
    );

    const footer = createElement('div', 'product-card__footer');
    const price = createElement(
        'p',
        'product-card__price',
        formatRupiah(product.price)
    );

    const stock = createElement(
        'p',
        'product-card__stock',
        Number(product.stock) > 0
            ? `Stok: ${product.stock}`
            : 'Stok habis'
    );

    const actions = createElement('div', 'product-card__actions');
    const button = createElement(
        'button',
        'js-add-to-cart',
        'Tambah ke Keranjang'
    );

    button.type = 'button';
    button.disabled = Number(product.stock) <= 0;

    button.addEventListener('click', () => {
        const result = addToCart(product, 1);

        if (result?.success) {
            showMessage(`${product.name} berhasil ditambahkan ke keranjang.`);
            updateCartBadge();
        } else {
            showMessage(
                result?.message || 'Produk gagal ditambahkan ke keranjang.',
                true
            );
        }
    });

    footer.append(price, stock);
    actions.append(button);
    body.append(category, name, description, footer, actions);
    card.append(imageWrap, body);

    return card;
}

function renderProducts(products) {
    elements.grid.replaceChildren();

    if (!products.length) {
        elements.grid.append(
            createElement(
                'p',
                'catalog-empty',
                'Produk tidak ditemukan. Coba ubah kata kunci atau filter.'
            )
        );

        elements.count.textContent = '0 produk ditemukan';
        return;
    }

    const fragment = document.createDocumentFragment();

    products.forEach(product => {
        fragment.append(createProductCard(product));
    });

    elements.grid.append(fragment);
    elements.count.textContent = `${products.length} produk ditemukan`;
}

function getFilterValues() {
    return {
        search: elements.search.value.trim(),
        category: elements.category.value,
        minPrice: elements.minPrice.value,
        maxPrice: elements.maxPrice.value,
        sort: elements.sort.value
    };
}

async function loadProducts() {
    elements.grid.setAttribute('aria-busy', 'true');
    elements.count.textContent = 'Memuat produk...';

    try {
        const filters = getFilterValues();
        const result = await getProducts(filters);

        if (!result?.success) {
            throw new Error(result?.message || 'Gagal mengambil data produk.');
        }

        allProducts = result.data || [];
        renderProducts(allProducts);
    } catch (error) {
        console.error('Gagal memuat katalog:', error);

        elements.grid.replaceChildren(
            createElement(
                'p',
                'catalog-error',
                'Produk gagal dimuat. Periksa koneksi database dan API.'
            )
        );

        elements.count.textContent = 'Gagal memuat produk';
    } finally {
        elements.grid.setAttribute('aria-busy', 'false');
    }
}

async function loadCategories() {
    try {
        const result = await getCategories();

        if (!result?.success) {
            throw new Error(result?.message || 'Gagal mengambil kategori.');
        }

        const categories = result.data || [];
        const selectedCategory =
            new URLSearchParams(window.location.search).get('category');

        categories.forEach(category => {
            const option = document.createElement('option');
            option.value = category.name;
            option.textContent = category.name;
            elements.category.append(option);
        });

        if (selectedCategory) {
            const matchingCategory = categories.find(
                category =>
                    category.name.toLowerCase() ===
                    selectedCategory.toLowerCase()
            );

            if (matchingCategory) {
                elements.category.value = matchingCategory.name;
            }
        }
    } catch (error) {
        console.error('Gagal memuat kategori:', error);
        showMessage('Kategori gagal dimuat. Filter kategori mungkin terbatas.', true);
    }
}

function debounceLoadProducts() {
    clearTimeout(debounceTimer);

    debounceTimer = setTimeout(() => {
        loadProducts();
    }, 250);
}

function initFilters() {
    elements.form.addEventListener('submit', event => {
        event.preventDefault();
        loadProducts();
    });

    elements.search.addEventListener('input', debounceLoadProducts);

    [
        elements.category,
        elements.minPrice,
        elements.maxPrice,
        elements.sort
    ].forEach(element => {
        element.addEventListener('change', loadProducts);
    });

    elements.reset.addEventListener('click', () => {
        elements.form.reset();

        const url = new URL(window.location.href);
        url.searchParams.delete('category');
        window.history.replaceState({}, '', url);

        loadProducts();
    });
}

const hamburger = document.querySelector('.navbar__hamburger');
const mobileMenu = document.querySelector('#mobile-menu');

hamburger?.addEventListener('click', () => {
    const isOpen = hamburger.getAttribute('aria-expanded') === 'true';

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

async function initCatalog() {
    updateCartBadge();

    window.addEventListener('scroll', () => {
        document.querySelector('.navbar')?.classList.toggle(
            'scrolled',
            window.scrollY > 50
        );
    });

    window.addEventListener('cart:updated', updateCartBadge);
    window.addEventListener('storage', updateCartBadge);

    initFilters();

    // Isi kategori terlebih dahulu supaya filter dari URL bisa diterapkan.
    await loadCategories();
    await loadProducts();
}

document.addEventListener('DOMContentLoaded', initCatalog);
