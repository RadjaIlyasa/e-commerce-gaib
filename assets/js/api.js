const IS_LOCAL = ['localhost', '127.0.0.1'].includes(
    window.location.hostname
);

export const API_BASE_URL = IS_LOCAL ? '/toko-dukun/api' : '/api';
export const ASSET_BASE_URL = IS_LOCAL ? '/toko-dukun/asset' : '/asset';

const TIMEOUT_MS = 10000;

/**
 * Send a JSON request and normalize network and server errors.
 */
async function apiFetch(url, options = {}) {
  const controller = new AbortController();
  const timeoutId = setTimeout(() => controller.abort(), TIMEOUT_MS);

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
    image: `${ASSET_BASE_URL}/${product.gambar}`,
    description: product.deskripsi || 'Belum ada deskripsi produk.',
  };
}

export async function getProducts(params = {}) {
  const result = await apiFetch(`${API_BASE_URL}/products.php`);

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
    `${API_BASE_URL}/categories.php`
  );

  if (!result.success) return result;

  return {
    ...result,
    data: result.data.map(category => ({
      id: Number(category.id_kategori),
      name: category.nama_kategori,
      count: Number(category.jumlah_produk),
      image: category.gambar
      ? `${ASSET_BASE_URL}/${category.gambar}`
      : `${ASSET_BASE_URL}/background.jpeg`,
    })),
  };
}

export async function createOrder(orderData) {
  return apiFetch(`${API_BASE_URL}/orders.php`, {
    method: 'POST',
    body: JSON.stringify(orderData),
  });
}

export function formatRupiah(amount) {
  return new Intl.NumberFormat('id-ID', {
    style: 'currency',
    currency: 'IDR',
    minimumFractionDigits: 0,
  }).format(amount);
}
