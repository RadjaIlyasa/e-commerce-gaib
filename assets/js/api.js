/**
 * PASAR GAIB — API Module
 * Semua komunikasi dengan backend PHP dilakukan melalui modul ini.
 * Ubah BASE_URL jika path deployment berbeda.
 */

// ── Konfigurasi ───────────────────────────────────────────────────────────────

const CONFIG = {
  // URL dasar API. Sesuaikan jika nama folder berbeda.
  BASE_URL: '/toko-dukun/api',

  // Aktifkan mock data jika backend belum tersedia.
  // Set ke false saat backend PHP sudah siap.
  USE_MOCK: false,

  // Timeout request dalam ms
  TIMEOUT_MS: 10000,
};

// ── Format Response Standar ───────────────────────────────────────────────────
// Backend PHP harus mengembalikan JSON dengan format ini:
//
// Sukses:
// { "success": true, "data": { ... }, "message": "..." }
//
// Gagal:
// { "success": false, "error": "KODE_ERROR", "message": "Pesan untuk user" }

// ── Mock Data (dev only) ──────────────────────────────────────────────────────

const MOCK_PRODUCTS = [
  {
    id: 1,
    name: 'Keris Pusaka Pamor Bumi',
    slug: 'keris-pusaka-pamor-bumi',
    category: 'Senjata Pusaka',
    price: 4750000,
    stock: 1,
    image: '/toko-dukun/asset/keris-pusaka.jpeg',
    description:
      'Keris buatan empu pilihan abad ke-14 dengan pamor bumi yang konon mampu mengusir energi negatif dari hunian. Bilah 13 luk, warangka kayu timoho.',
    featured: true,
  },
  {
    id: 2,
    name: 'Jenglot Petilasan Demak',
    slug: 'jenglot-petilasan-demak',
    category: 'Makhluk Tersegel',
    price: 3200000,
    stock: 2,
    image: '/toko-dukun/asset/jenglot.jpeg',
    description:
      'Jenglot autentik dari situs petilasan di kawasan Demak. Disertai surat keterangan asal-usul dan cara perawatan.',
    featured: true,
  },
  {
    id: 3,
    name: 'Boneka Pengikat Arwah',
    slug: 'boneka-pengikat-arwah',
    category: 'Jimat & Mantra',
    price: 1850000,
    stock: 5,
    image: '/toko-dukun/asset/boneka-santet.jpeg',
    description:
      'Boneka wanita dibungkus kain kafan tua, dilengkapi rambut asli. Digunakan dalam ritual tertentu. Hanya untuk kolektor dewasa.',
    featured: true,
  },
  {
    id: 4,
    name: 'Jarum Susuk Emas Keramat',
    slug: 'jarum-susuk-emas-keramat',
    category: 'Jimat & Mantra',
    price: 2300000,
    stock: 8,
    image: '/toko-dukun/asset/jarum-susuk.jpeg',
    description:
      'Jarum susuk dari emas 22 karat yang telah melalui proses ritual 7 malam. Dipercaya meningkatkan pesona dan kewibawaan pemakainya.',
    featured: false,
  },
  {
    id: 5,
    name: 'Batu Akik Giok Naga',
    slug: 'batu-akik-giok-naga',
    category: 'Batu Bertuah',
    price: 980000,
    stock: 12,
    image: '/toko-dukun/asset/batu-akik.jpeg',
    description:
      'Batu giok asli Kalimantan dengan pola serat menyerupai naga. Dikenal di kalangan penggemar batu akik sebagai batu rezeki.',
    featured: false,
  },
  {
    id: 6,
    name: 'Jelangkung Permainan Takdir',
    slug: 'jelangkung-permainan-takdir',
    category: 'Media Pemanggil',
    price: 750000,
    stock: 3,
    image: '/toko-dukun/asset/jelangkung.jpeg',
    description:
      'Set jelangkung lengkap dengan tempurung kelapa, ijuk, dan kain putih. Disertai panduan ritual yang aman dan cara menutup sesi.',
    featured: false,
  },
  {
    id: 7,
    name: 'Kartu Tarot Nusantara',
    slug: 'kartu-tarot-nusantara',
    category: 'Alat Ramalan',
    price: 450000,
    stock: 20,
    image: '/toko-dukun/asset/karrtu-tarot.jpeg',
    description:
      'Deck tarot 78 kartu bergambar wayang dan mitologi Nusantara. Ilustrasi tangan oleh seniman lokal Yogyakarta, dicetak di atas kertas linen premium.',
    featured: true,
  },
  {
    id: 8,
    name: 'Topeng Rangda Bali',
    slug: 'topeng-rangda-bali',
    category: 'Artefak Ritual',
    price: 5500000,
    stock: 1,
    image: '/toko-dukun/asset/topeng-rangda.jpeg',
    description:
      'Topeng Rangda sakral ukiran kayu pule dari pengrajin Ubud. Digunakan dalam pertunjukan Calonarang. Sudah melalui upacara pamarisudha.',
    featured: true,
  },
  {
    id: 9,
    name: 'Wayang Kulit Sukma',
    slug: 'wayang-kulit-sukma',
    category: 'Artefak Ritual',
    price: 2100000,
    stock: 4,
    image: '/toko-dukun/asset/wayang-kulit.jpeg',
    description:
      'Wayang kulit tokoh Gatotkaca dari kulit kerbau pilihan, gapit tanduk kerbau, tatahan halus motif bledeg. Karya dalang seniman Surakarta.',
    featured: false,
  },
];

const MOCK_CATEGORIES = [
  { id: 1, name: 'Senjata Pusaka',   count: 1, image: '/toko-dukun/asset/keris-pusaka.jpeg' },
  { id: 2, name: 'Makhluk Tersegel', count: 2, image: '/toko-dukun/asset/jenglot.jpeg' },
  { id: 3, name: 'Jimat & Mantra',   count: 2, image: '/toko-dukun/asset/boneka-santet.jpeg' },
  { id: 4, name: 'Batu Bertuah',     count: 1, image: '/toko-dukun/asset/batu-akik.jpeg' },
  { id: 5, name: 'Alat Ramalan',     count: 1, image: '/toko-dukun/asset/karrtu-tarot.jpeg' },
  { id: 6, name: 'Artefak Ritual',   count: 2, image: '/toko-dukun/asset/topeng-rangda.jpeg' },
];

// ── Helpers ───────────────────────────────────────────────────────────────────

/**
 * Fetch dengan timeout dan penanganan error standar.
 * @param {string} url
 * @param {RequestInit} [options]
 * @returns {Promise<{success: boolean, data?: any, error?: string, message?: string}>}
 */
async function apiFetch(url, options = {}) {
  const controller = new AbortController();
  const timeoutId = setTimeout(() => controller.abort(), CONFIG.TIMEOUT_MS);

  try {
    const res = await fetch(url, {
      ...options,
      signal: controller.signal,
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        ...options.headers,
      },
    });

    clearTimeout(timeoutId);

    // Coba parse JSON terlepas dari status HTTP
    let body;
    const contentType = res.headers.get('Content-Type') || '';
    if (contentType.includes('application/json')) {
      body = await res.json();
    } else {
      const text = await res.text();
      throw new Error(`Server mengembalikan non-JSON: ${text.slice(0, 200)}`);
    }

    if (!res.ok) {
      return {
        success: false,
        error: body?.error || 'SERVER_ERROR',
        message: body?.message || `HTTP ${res.status}`,
      };
    }

    return body;
  } catch (err) {
    clearTimeout(timeoutId);

    if (err.name === 'AbortError') {
      return { success: false, error: 'TIMEOUT', message: 'Koneksi ke server habis waktu. Coba lagi.' };
    }

    if (!navigator.onLine) {
      return { success: false, error: 'OFFLINE', message: 'Tidak ada koneksi internet.' };
    }

    return { success: false, error: 'NETWORK_ERROR', message: err.message || 'Gagal terhubung ke server.' };
  }
}

// ── Delay mock (simulasi network) ─────────────────────────────────────────────
function mockDelay(ms = 600) {
  return new Promise(resolve => setTimeout(resolve, ms));
}

// Mengubah format data database agar cocok dengan frontend.
function normalizeProduct(product) {
  return {
    id: Number(product.id_produk),
    name: product.nama_produk,
    slug: String(product.nama_produk)
      .toLowerCase()
      .replace(/[^a-z0-9]+/g, '-')
      .replace(/^-|-$/g, ''),
    category: product.nama_kategori,
    price: Number(product.harga),
    stock: Number(product.stok),
    image: `/toko-dukun/asset/${product.gambar}`,
    description: product.deskripsi || 'Belum ada deskripsi produk.',
  };
}

// ── API Endpoints ─────────────────────────────────────────────────────────────

/**
 * GET daftar produk dengan filter opsional.
 * @param {Object} params - { search, category, minPrice, maxPrice, sort, page }
 */
export async function getProducts(params = {}) {
  const result = await apiFetch(`${CONFIG.BASE_URL}/products.php`);

  if (!result.success) return result;

  let products = result.data.map(normalizeProduct);

  if (params.search) {
    const q = params.search.toLowerCase();

    products = products.filter(p =>
      p.name.toLowerCase().includes(q) ||
      p.description.toLowerCase().includes(q) ||
      p.category.toLowerCase().includes(q)
    );
  }

  if (params.category) {
    products = products.filter(p =>
      p.category === params.category
    );
  }

  if (params.minPrice !== undefined && params.minPrice !== '') {
    products = products.filter(p =>
      p.price >= Number(params.minPrice)
    );
  }

  if (params.maxPrice !== undefined && params.maxPrice !== '') {
    products = products.filter(p =>
      p.price <= Number(params.maxPrice)
    );
  }

  if (params.sort === 'price_asc') {
    products.sort((a, b) => a.price - b.price);
  }

  if (params.sort === 'price_desc') {
    products.sort((a, b) => b.price - a.price);
  }

  if (params.sort === 'newest') {
    products.reverse();
  }

  return {
    success: true,
    data: products,
    total: products.length,
  };
}

export async function getFeaturedProducts() {
  const result = await getProducts();

  if (!result.success) return result;

  return {
    ...result,
    data: result.data.slice(0, 4),
  };
}

export async function getCategories() {
  const result = await apiFetch(
    `${CONFIG.BASE_URL}/categories.php`
  );

  if (!result.success) return result;

  return {
    ...result,
    data: result.data.map(category => ({
      id: Number(category.id_kategori),
      name: category.nama_kategori,
      count: Number(category.jumlah_produk),
      image: category.gambar
        ? `/toko-dukun/asset/${category.gambar}`
        : '/toko-dukun/asset/background.jpeg',
    })),
  };
}

/**
 * POST membuat pesanan baru.
 * @param {Object} orderData
 */
export async function createOrder(orderData) {
  if (CONFIG.USE_MOCK) {
    await mockDelay(1200);
    // Simulasi validasi stok
    const outOfStock = orderData.items.find(item => {
      const p = MOCK_PRODUCTS.find(p => p.id === item.product_id);
      return p && p.stock < item.quantity;
    });
    if (outOfStock) {
      return { success: false, error: 'OUT_OF_STOCK', message: 'Stok produk tidak mencukupi.' };
    }
    return {
      success: true,
      data: {
        order_id: `PG-${Date.now()}`,
        status: 'pending',
        total: orderData.items.reduce((sum, i) => {
          const p = MOCK_PRODUCTS.find(p => p.id === i.product_id);
          return sum + (p ? p.price * i.quantity : 0);
        }, 0),
      },
      message: 'Pesanan berhasil dibuat.',
    };
  }
  return apiFetch(`${CONFIG.BASE_URL}/orders.php`, {
    method: 'POST',
    body: JSON.stringify(orderData),
  });
}

/**
 * POST login admin.
 * @param {string} username
 * @param {string} password
 */
export async function adminLogin(username, password) {
  if (CONFIG.USE_MOCK) {
    await mockDelay(800);
    if (username === 'admin' && password === 'pasar123') {
      const token = btoa(`admin:${Date.now()}`);
      return { success: true, data: { token, name: 'Admin Pasar Gaib' } };
    }
    return { success: false, error: 'INVALID_CREDENTIALS', message: 'Username atau password salah.' };
  }
  return apiFetch(`${CONFIG.BASE_URL}/auth.php`, {
    method: 'POST',
    body: JSON.stringify({ username, password }),
  });
}

/**
 * GET daftar pesanan untuk admin.
 * @param {string} token - Auth token dari session
 */
export async function getAdminOrders(token) {
  if (CONFIG.USE_MOCK) {
    await mockDelay(600);
    return {
      success: true,
      data: [
        { id: 'PG-001', customer: 'Budi Santoso', total: 4750000, status: 'pending',  items: 1, date: '2026-10-28' },
        { id: 'PG-002', customer: 'Sari Dewi',    total: 3200000, status: 'confirmed', items: 1, date: '2026-10-27' },
      ],
    };
  }
  return apiFetch(`${CONFIG.BASE_URL}/admin/orders.php`, {
    headers: { Authorization: `Bearer ${token}` },
  });
}

/**
 * PATCH update status pesanan.
 * @param {string} orderId
 * @param {string} status - pending | confirmed | shipped | done | cancelled
 * @param {string} token
 */
export async function updateOrderStatus(orderId, status, token) {
  if (CONFIG.USE_MOCK) {
    await mockDelay(500);
    return { success: true, message: 'Status pesanan diperbarui.' };
  }
  return apiFetch(`${CONFIG.BASE_URL}/admin/order-status.php`, {
    method: 'PATCH',
    headers: { Authorization: `Bearer ${token}` },
    body: JSON.stringify({ order_id: orderId, status }),
  });
}

// ── Utility Ekspor ────────────────────────────────────────────────────────────

/**
 * Format harga ke Rupiah.
 * @param {number} amount
 * @returns {string}
 */
export function formatRupiah(amount) {
  return new Intl.NumberFormat('id-ID', {
    style: 'currency',
    currency: 'IDR',
    minimumFractionDigits: 0,
  }).format(amount);
}

/**
 * Tandai CONFIG sebagai tidak bisa diubah dari luar.
 * Cek apakah mock sedang aktif.
 */
export function isMockActive() {
  return CONFIG.USE_MOCK;
}
