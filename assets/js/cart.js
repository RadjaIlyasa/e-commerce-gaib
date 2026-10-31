/**
 * PASAR GAIB — cart.js
 * Manajemen keranjang menggunakan localStorage.
 * Hanya menyimpan: product_id, name, price, image, max_stock, quantity.
 * Tidak menyimpan data pribadi pelanggan.
 */

const CART_KEY = 'pasar_gaib_cart';

// ── Read / Write ──────────────────────────────────────────────────────────────

export function getCart() {
  try {
    return JSON.parse(localStorage.getItem(CART_KEY)) || [];
  } catch {
    return [];
  }
}

function saveCart(items) {
  localStorage.setItem(CART_KEY, JSON.stringify(items));
  window.dispatchEvent(new CustomEvent('cart:updated', { detail: { cart: items } }));
}

// ── Operasi Keranjang ─────────────────────────────────────────────────────────

/**
 * Tambah produk ke keranjang.
 * @param {{ id, name, price, image, stock }} product
 * @param {number} quantity
 * @returns {{ success: boolean, message: string }}
 */
export function addToCart(product, quantity = 1) {
  if (!product?.id) return { success: false, message: 'Data produk tidak valid.' };
  if (quantity < 1)  return { success: false, message: 'Jumlah minimal 1.' };

  const cart  = getCart();
  const index = cart.findIndex(item => item.product_id === product.id);

  if (index > -1) {
    const newQty = cart[index].quantity + quantity;
    if (newQty > cart[index].max_stock) {
      return { success: false, message: `Stok tersisa hanya ${cart[index].max_stock} unit.` };
    }
    cart[index].quantity = newQty;
  } else {
    if (quantity > product.stock) {
      return { success: false, message: `Stok tersisa hanya ${product.stock} unit.` };
    }
    cart.push({
      product_id: product.id,
      name:       product.name,
      price:      product.price,
      image:      product.image,
      max_stock:  product.stock,
      quantity,
    });
  }

  saveCart(cart);
  return { success: true, message: `${product.name} ditambahkan ke keranjang.` };
}

/**
 * Update kuantitas item.
 * @param {number} productId
 * @param {number} quantity - jumlah baru (0 = hapus)
 */
export function updateQuantity(productId, quantity) {
  const cart = getCart();
  const index = cart.findIndex(item => item.product_id === productId);
  if (index === -1) return;

  if (quantity <= 0) {
    cart.splice(index, 1);
  } else {
    cart[index].quantity = Math.min(quantity, cart[index].max_stock);
  }
  saveCart(cart);
}

/**
 * Hapus satu item dari keranjang.
 * @param {number} productId
 */
export function removeFromCart(productId) {
  const cart = getCart().filter(item => item.product_id !== productId);
  saveCart(cart);
}

/** Kosongkan seluruh keranjang. */
export function clearCart() {
  saveCart([]);
}

// ── Kalkulasi ─────────────────────────────────────────────────────────────────

/** Jumlah total item (qty) di keranjang. */
export function getCartCount() {
  return getCart().reduce((sum, item) => sum + item.quantity, 0);
}

/** Subtotal keranjang sebelum ongkir. */
export function getCartSubtotal() {
  return getCart().reduce((sum, item) => sum + item.price * item.quantity, 0);
}

/** Estimasi ongkir sederhana (simulasi — backend yang menentukan final). */
export function estimateShipping(subtotal) {
  if (subtotal === 0) return 0;
  if (subtotal >= 5000000) return 0;     // gratis ongkir > 5 juta
  if (subtotal >= 1000000) return 25000;
  return 50000;
}
