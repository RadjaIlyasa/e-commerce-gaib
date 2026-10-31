/**
 * PASAR GAIB — app.js
 * Logika JavaScript untuk beranda (index.html):
 * - Inisialisasi navbar (scroll state, hamburger, cart badge)
 * - Muat produk unggulan
 * - Muat kategori
 * - Toast notification
 */

import { getFeaturedProducts, getCategories, formatRupiah } from './api.js';
import { getCartCount, addToCart } from './cart.js';

// ── Navbar ────────────────────────────────────────────────────────────────────

function initNavbar() {
  const navbar     = document.querySelector('.navbar');
  const hamburger  = document.querySelector('.navbar__hamburger');
  const mobileMenu = document.querySelector('.navbar__mobile-menu');

  // Scroll state
  function onScroll() {
    if (window.scrollY > 40) {
      navbar?.classList.add('scrolled');
    } else {
      navbar?.classList.remove('scrolled');
    }
  }
  window.addEventListener('scroll', onScroll, { passive: true });
  onScroll();

  // Hamburger toggle
  if (hamburger && mobileMenu) {
    hamburger.addEventListener('click', () => {
      const isOpen = mobileMenu.classList.toggle('open');
      hamburger.setAttribute('aria-expanded', String(isOpen));
      hamburger.setAttribute('aria-label', isOpen ? 'Tutup menu' : 'Buka menu');
      document.body.style.overflow = isOpen ? 'hidden' : '';
    });

    // Tutup menu saat link diklik
    mobileMenu.querySelectorAll('a').forEach(link => {
      link.addEventListener('click', () => {
        mobileMenu.classList.remove('open');
        hamburger.setAttribute('aria-expanded', 'false');
        document.body.style.overflow = '';
      });
    });

    // Tutup menu saat klik di luar
    document.addEventListener('click', (e) => {
      if (!navbar?.contains(e.target) && !mobileMenu?.contains(e.target)) {
        if (mobileMenu.classList.contains('open')) {
          mobileMenu.classList.remove('open');
          hamburger.setAttribute('aria-expanded', 'false');
          document.body.style.overflow = '';
        }
      }
    });
  }

  // Tandai link aktif
  const currentPath = window.location.pathname;
  document.querySelectorAll('.navbar__link, .navbar__mobile-link').forEach(link => {
    const href = link.getAttribute('href');
    if (!href) return;
    const isHome = (href === 'index.html' || href === '/toko-dukun/' || href === '/toko-dukun/index.html');
    if (isHome && (currentPath.endsWith('index.html') || currentPath.endsWith('/toko-dukun/') || currentPath === '/toko-dukun')) {
      link.classList.add('active');
    } else if (!isHome && href && currentPath.includes(href.replace('.html', ''))) {
      link.classList.add('active');
    }
  });
}

// ── Cart Badge ────────────────────────────────────────────────────────────────

function updateCartBadge() {
  const count   = getCartCount();
  const badges  = document.querySelectorAll('.navbar__cart-badge');
  badges.forEach(badge => {
    badge.textContent = count > 99 ? '99+' : String(count);
    badge.classList.toggle('visible', count > 0);
  });
}

function initCartBadge() {
  updateCartBadge();
  window.addEventListener('cart:updated', updateCartBadge);
}

// ── Toast ─────────────────────────────────────────────────────────────────────

function showToast(message, type = 'info', duration = 3500) {
  let container = document.querySelector('.toast-container');
  if (!container) {
    container = document.createElement('div');
    container.className = 'toast-container';
    container.setAttribute('role', 'status');
    container.setAttribute('aria-live', 'polite');
    document.body.appendChild(container);
  }

  const icons = { success: '✓', error: '✕', info: '◆' };

  const toast = document.createElement('div');
  toast.className = `toast toast--${type}`;
  toast.innerHTML = `
    <span style="font-size:1rem;color:var(--clr-${type === 'success' ? 'success' : type === 'error' ? 'error' : 'gold'})">${icons[type] || '◆'}</span>
    <span>${message}</span>
  `;

  container.appendChild(toast);

  const remove = () => {
    toast.classList.add('removing');
    toast.addEventListener('animationend', () => toast.remove(), { once: true });
  };

  setTimeout(remove, duration);
  toast.addEventListener('click', remove);
}

// Ekspor showToast agar bisa dipakai halaman lain
window.showToast = showToast;

// ── Produk Unggulan ───────────────────────────────────────────────────────────

function createProductCard(product) {
  const isHabis    = product.stock === 0;
  const isLowStock = product.stock > 0 && product.stock <= 3;

  const card = document.createElement('article');
  card.className = 'product-card';
  card.innerHTML = `
    <div class="product-card__image-wrap">
      <img
        src="${product.image}"
        alt="${product.name}"
        loading="lazy"
        decoding="async"
        width="400"
        height="300"
      />
      ${isHabis
        ? `<span class="product-card__badge product-card__badge--habis">Habis</span>`
        : `<span class="product-card__badge">${product.category}</span>`
      }
    </div>
    <div class="product-card__body">
      <p class="product-card__category">${product.category}</p>
      <h3 class="product-card__name">${product.name}</h3>
      <p class="product-card__desc">${product.description}</p>
      <div class="product-card__footer">
        <span class="product-card__price">${formatRupiah(product.price)}</span>
        <span class="product-card__stock ${isLowStock ? 'product-card__stock--low' : ''}">
          ${isHabis ? '—' : isLowStock ? `Sisa ${product.stock}` : `Stok ${product.stock}`}
        </span>
      </div>
      <div class="product-card__actions">
        <a
          href="product-detail.html?id=${product.id}"
          class="btn btn--secondary product-card__btn"
        >
          Lihat Detail
        </a>
        ${!isHabis ? `
        <button
          class="btn btn--primary product-card__btn js-add-to-cart"
          data-product-id="${product.id}"
          aria-label="Tambah ${product.name} ke keranjang"
        >
          + Keranjang
        </button>
        ` : `
        <button class="btn btn--ghost product-card__btn" disabled aria-disabled="true">
          Habis
        </button>
        `}
      </div>
    </div>
  `;

  // Event handler tombol keranjang
  const cartBtn = card.querySelector('.js-add-to-cart');
  if (cartBtn) {
    cartBtn.addEventListener('click', (e) => {
      e.preventDefault();
      const result = addToCart(product, 1);
      showToast(result.message, result.success ? 'success' : 'error');
      if (result.success) {
        cartBtn.textContent = '✓ Ditambahkan';
        cartBtn.disabled = true;
        setTimeout(() => {
          cartBtn.textContent = '+ Keranjang';
          cartBtn.disabled = false;
        }, 1500);
      }
    });
  }

  return card;
}

async function loadFeaturedProducts() {
  const grid = document.getElementById('featured-grid');
  if (!grid) return;

  // Loading state
  grid.innerHTML = `
    <div class="state-loading" style="grid-column:1/-1">
      <div class="spinner" aria-hidden="true"></div>
      <p>Memuat koleksi pilihan…</p>
    </div>
  `;

  const result = await getFeaturedProducts();

  if (!result.success) {
    grid.innerHTML = `
      <div class="state-error" style="grid-column:1/-1">
        <div class="state-error__icon">⚠</div>
        <p>Gagal memuat produk. ${result.message}</p>
        <button class="btn btn--secondary btn--sm" style="margin-top:var(--space-4)" id="retry-featured">
          Coba Lagi
        </button>
      </div>
    `;
    document.getElementById('retry-featured')?.addEventListener('click', loadFeaturedProducts);
    return;
  }

  if (!result.data?.length) {
    grid.innerHTML = `
      <div class="state-empty" style="grid-column:1/-1">
        <p>Belum ada produk unggulan saat ini.</p>
      </div>
    `;
    return;
  }

  grid.innerHTML = '';
  result.data.forEach(product => {
    grid.appendChild(createProductCard(product));
  });
}

// ── Kategori ─────────────────────────────────────────────────────────────────

function createCategoryCard(cat) {
  const a = document.createElement('a');
  a.className = 'category-card';
  a.href = `products.html?category=${encodeURIComponent(cat.name)}`;
  a.setAttribute('aria-label', `Lihat produk kategori ${cat.name}`);
  a.innerHTML = `
    <img
      src="${cat.image}"
      alt="${cat.name}"
      loading="lazy"
      decoding="async"
      class="category-card__img"
      width="200"
      height="267"
    />
    <div class="category-card__overlay">
      <h3 class="category-card__name">${cat.name}</h3>
      <span class="category-card__count">${cat.count} produk</span>
    </div>
  `;
  return a;
}

async function loadCategories() {
  const grid = document.getElementById('categories-grid');
  if (!grid) return;

  grid.innerHTML = `<div class="state-loading"><div class="spinner" aria-hidden="true"></div></div>`;

  const result = await getCategories();

  if (!result.success || !result.data?.length) {
    grid.innerHTML = '';
    return;
  }

  grid.innerHTML = '';
  result.data.forEach(cat => {
    grid.appendChild(createCategoryCard(cat));
  });
}

// ── Scroll Reveal sederhana ───────────────────────────────────────────────────

function initScrollReveal() {
  const elements = document.querySelectorAll('[data-reveal]');
  if (!elements.length) return;

  const observer = new IntersectionObserver(
    (entries) => {
      entries.forEach(entry => {
        if (entry.isIntersecting) {
          entry.target.classList.add('revealed');
          observer.unobserve(entry.target);
        }
      });
    },
    { threshold: 0.12, rootMargin: '0px 0px -40px 0px' }
  );

  elements.forEach(el => {
    el.style.cssText = `
      opacity: 0;
      transform: translateY(20px);
      transition: opacity 0.55s ease, transform 0.55s ease;
    `;
    observer.observe(el);
  });

  document.addEventListener('revealed-style', () => {
    document.querySelectorAll('.revealed').forEach(el => {
      el.style.opacity = '1';
      el.style.transform = 'translateY(0)';
    });
  });

  // Polyfill minimal jika sudah revealed
  document.querySelectorAll('.revealed').forEach(el => {
    el.style.opacity = '1';
    el.style.transform = 'translateY(0)';
  });
}

// IntersectionObserver tidak punya event, handle via MutationObserver kecil
function watchReveal() {
  const observer = new MutationObserver((mutations) => {
    mutations.forEach(m => {
      if (m.type === 'attributes' && m.attributeName === 'class') {
        const el = m.target;
        if (el.classList.contains('revealed')) {
          el.style.opacity = '1';
          el.style.transform = 'translateY(0)';
        }
      }
    });
  });

  document.querySelectorAll('[data-reveal]').forEach(el => {
    observer.observe(el, { attributes: true });
  });
}

// ── Inisialisasi ──────────────────────────────────────────────────────────────

document.addEventListener('DOMContentLoaded', () => {
  initNavbar();
  initCartBadge();
  loadFeaturedProducts();
  loadCategories();
  initScrollReveal();
  watchReveal();
});
