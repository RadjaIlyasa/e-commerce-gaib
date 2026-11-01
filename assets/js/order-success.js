
import {
    API_BASE_URL,
    ASSET_BASE_URL,
    formatRupiah
} from './api.js';

const elements = {
    loading: document.getElementById('order-loading'),
    content: document.getElementById('order-content'),
    error: document.getElementById('order-error'),

    orderCode: document.getElementById('order-code'),
    orderDate: document.getElementById('order-date'),
    customerName: document.getElementById('customer-name'),
    customerPhone: document.getElementById('customer-phone'),
    customerEmail: document.getElementById('customer-email'),
    customerAddress: document.getElementById('customer-address'),

    paymentMethod: document.getElementById('payment-method'),
    paymentStatus: document.getElementById('payment-status'),
    orderStatus: document.getElementById('order-status'),

    productList: document.getElementById('order-products'),
    subtotal: document.getElementById('order-subtotal'),
    total: document.getElementById('order-total'),
};

const params = new URLSearchParams(window.location.search);
const orderCode = params.get('kode');

function showError(message) {
    elements.loading.hidden = true;
    elements.content.hidden = true;
    elements.error.hidden = false;
    elements.error.textContent = message;
}

function formatDate(date) {
    return new Date(date).toLocaleString('id-ID', {
        dateStyle: 'long',
        timeStyle: 'short',
    });
}

function renderProducts(products) {
    elements.productList.innerHTML = '';

    products.forEach(product => {
        const item = document.createElement('div');
        item.className = 'order-product';

        const image = document.createElement('img');
        image.src = product.gambar
            ? `${ASSET_BASE_URL}/${product.gambar}`
            : `${ASSET_BASE_URL}/background.jpeg`;
        image.alt = product.nama_produk;

        const info = document.createElement('div');
        info.className = 'order-product-info';

        const name = document.createElement('h4');
        name.textContent = product.nama_produk;

        const quantity = document.createElement('p');
        quantity.textContent = `${product.jumlah} × ${formatRupiah(Number(product.harga_satuan))}`;

        const price = document.createElement('strong');
        price.textContent = formatRupiah(Number(product.subtotal));

        info.append(name, quantity);
        item.append(image, info, price);

        elements.productList.appendChild(item);
    });
}

async function loadOrder() {
    if (!orderCode) {
        showError('Kode pesanan tidak ditemukan.');
        return;
    }

    try {
        const response = await fetch(
            `${API_BASE_URL}/order-detail.php?kode=${encodeURIComponent(orderCode)}`,
            {
                headers: {
                    Accept: 'application/json',
                },
            }
        );

        const result = await response.json();

        if (!response.ok || !result.success) {
            throw new Error(result.message || 'Gagal mengambil detail pesanan.');
        }

        const order = result.data.pesanan;
        const products = result.data.produk;

        elements.orderCode.textContent = order.kode_pesanan;
        elements.orderDate.textContent = formatDate(order.created_at);

        elements.customerName.textContent = order.nama_pelanggan;
        elements.customerPhone.textContent = order.no_telepon;
        elements.customerEmail.textContent = order.email || '-';
        elements.customerAddress.textContent = order.alamat_pengiriman;

        elements.paymentMethod.textContent = order.metode_pembayaran;
        elements.paymentStatus.textContent = order.status_pembayaran.replaceAll('_', ' ');
        elements.orderStatus.textContent = order.status_pesanan.replaceAll('_', ' ');

        renderProducts(products);

        const subtotal = products.reduce(
            (sum, product) => sum + Number(product.subtotal),
            0
        );

        elements.subtotal.textContent = formatRupiah(subtotal);
        elements.total.textContent = formatRupiah(Number(order.total_harga));

        elements.loading.hidden = true;
        elements.content.hidden = false;

    } catch (error) {
        console.error('Order detail error:', error);
        showError(error.message);
    }
}

loadOrder();
