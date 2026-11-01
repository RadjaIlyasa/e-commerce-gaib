
import { API_BASE_URL, formatRupiah } from './api.js';

const elements = {
    loading: document.querySelector('#payment-loading'),
    content: document.querySelector('#payment-content'),
    error: document.querySelector('#payment-error'),
    errorTitle: document.querySelector('#payment-error-title'),
    errorMessage: document.querySelector('#payment-error-message'),
    success: document.querySelector('#payment-success'),
    successCode: document.querySelector('#payment-success-code'),
    orderCode: document.querySelector('#payment-order-code'),
    total: document.querySelector('#payment-total'),
    countdown: document.querySelector('#payment-countdown'),
    message: document.querySelector('#payment-message'),
    confirm: document.querySelector('#payment-confirm'),
    account: document.querySelector('#bank-account'),
    copyButton: document.querySelector('#copy-account'),
    copyMessage: document.querySelector('#copy-message')
};

const params = new URLSearchParams(window.location.search);
const orderCode = params.get('kode');

let countdownDeadline = null;
let countdownInterval = null;
let pollingInterval = null;
let isProcessing = false;
let isChecking = false;

function stopTimers() {
    if (countdownInterval) {
        clearInterval(countdownInterval);
        countdownInterval = null;
    }

    if (pollingInterval) {
        clearInterval(pollingInterval);
        pollingInterval = null;
    }
}

function showError(title, message) {
    stopTimers();

    elements.loading.hidden = true;
    elements.content.hidden = true;
    elements.success.hidden = true;

    elements.errorTitle.textContent = title;
    elements.errorMessage.textContent = message;
    elements.error.hidden = false;
}

function showSuccess(code) {
    stopTimers();

    elements.loading.hidden = true;
    elements.content.hidden = true;
    elements.error.hidden = true;

    elements.successCode.textContent = `Nomor pesanan: ${code}`;

    const successLink = elements.success.querySelector('a');

    if (successLink) {
        successLink.href =
            `order-success.html?kode=${encodeURIComponent(code)}`;
    }

    elements.success.hidden = false;
}

function showMessage(message) {
    elements.message.textContent = message;
    elements.message.hidden = false;
}

async function postJSON(endpoint, data) {
    const response = await fetch(`${API_BASE_URL}/${endpoint}`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json'
        },
        body: JSON.stringify(data)
    });

    const result = await response.json();

    return {
        httpOk: response.ok,
        data: result
    };
}

function startCountdown(seconds) {
    // Sinkronkan deadline dengan waktu terbaru dari server.
    countdownDeadline = Date.now() + Math.max(0, seconds) * 1000;

    if (countdownInterval) return;

    function updateCountdown() {
        const remaining = Math.max(
            0,
            Math.ceil((countdownDeadline - Date.now()) / 1000)
        );

        const minutes = Math.floor(remaining / 60);
        const secondsLeft = remaining % 60;

        elements.countdown.textContent =
            `${String(minutes).padStart(2, '0')}:${String(secondsLeft).padStart(2, '0')}`;

        if (remaining <= 0) {
            clearInterval(countdownInterval);
            countdownInterval = null;

            showMessage(
                'Waktu pembayaran habis. Sedang memeriksa status pesanan...'
            );

            checkPaymentStatus();
        }
    }

    updateCountdown();
    countdownInterval = setInterval(updateCountdown, 1000);
}

function handlePaymentStatus(data) {
    if (data.status_pembayaran === 'berhasil') {
        showSuccess(orderCode);
        return true;
    }

    if (data.status_pembayaran === 'kedaluwarsa') {
        showError(
            'Pembayaran Kedaluwarsa',
            'Waktu pembayaran telah habis. Pesanan dibatalkan dan stok dikembalikan.'
        );

        return true;
    }

    if (data.status_pembayaran !== 'menunggu_pembayaran') {
        showError(
            'Status Pembayaran Tidak Valid',
            'Pesanan ini tidak lagi menunggu pembayaran.'
        );

        return true;
    }

    return false;
}

async function checkPaymentStatus() {
    if (isChecking || !orderCode) return;

    isChecking = true;

    try {
        const response = await postJSON(
            'payment-status.php',
            { kode_pesanan: orderCode }
        );

        if (!response.httpOk || response.data.success !== true) {
            elements.loading.hidden = true;
            elements.content.hidden = false;

            showMessage(
                response.data.message || 'Gagal memeriksa status pembayaran.'
            );

            return;
        }

        const data = response.data;

        if (data.metode_pembayaran !== 'TRANSFER') {
            showError(
                'Metode Pembayaran Tidak Sesuai',
                'Halaman ini hanya digunakan untuk pembayaran Transfer Bank.'
            );

            return;
        }

        if (handlePaymentStatus(data)) return;

        elements.orderCode.textContent = data.kode_pesanan;
        elements.total.textContent = formatRupiah(
            Number(data.total_harga)
        );

        elements.loading.hidden = true;
        elements.content.hidden = false;

        if (data.sisa_detik !== undefined) {
            startCountdown(Number(data.sisa_detik));
        }

        if (Number(data.sisa_detik) <= 0) {
            showMessage(
                'Waktu pembayaran habis. Sedang memeriksa status pesanan...'
            );
        }

    } catch (error) {
        elements.loading.hidden = true;
        elements.content.hidden = false;

        showMessage(
            'Tidak dapat terhubung ke server. Periksa koneksi lalu coba lagi.'
        );
    } finally {
        isChecking = false;
    }
}

async function confirmPayment() {
    if (isProcessing) return;

    isProcessing = true;
    elements.confirm.disabled = true;
    elements.confirm.textContent = 'Memproses Konfirmasi...';
    elements.message.hidden = true;

    try {
        const response = await postJSON(
            'payment-confirm.php',
            { kode_pesanan: orderCode }
        );

        if (response.data.success === true) {
            showSuccess(orderCode);
            return;
        }

        if (response.data.status_pembayaran === 'kedaluwarsa') {
            showError(
                'Pembayaran Kedaluwarsa',
                response.data.message
            );

            return;
        }

        showMessage(
            response.data.message || 'Pembayaran gagal dikonfirmasi.'
        );

    } catch (error) {
        showMessage(
            'Terjadi kesalahan saat mengonfirmasi pembayaran.'
        );
    } finally {
        isProcessing = false;

        if (elements.success.hidden && elements.error.hidden) {
            elements.confirm.disabled = false;
            elements.confirm.textContent =
                'Simulasikan Pembayaran Berhasil';
        }
    }
}

async function copyAccountNumber() {
    const accountNumber = elements.account.textContent.trim();

    try {
        await navigator.clipboard.writeText(accountNumber);

        elements.copyMessage.textContent =
            'Nomor rekening berhasil disalin!';

        elements.copyMessage.hidden = false;

    } catch (error) {
        elements.copyMessage.textContent =
            'Gagal menyalin. Silakan salin nomor rekening secara manual.';

        elements.copyMessage.hidden = false;
    }
}

async function initializePayment() {
    if (!orderCode) {
        showError(
            'Nomor Pesanan Tidak Ditemukan',
            'Buka halaman pembayaran melalui proses checkout.'
        );

        return;
    }

    elements.orderCode.textContent = orderCode;

    await checkPaymentStatus();

    if (elements.error.hidden && elements.success.hidden) {
        pollingInterval = setInterval(checkPaymentStatus, 5000);
    }
}

elements.confirm.addEventListener('click', confirmPayment);
elements.copyButton.addEventListener('click', copyAccountNumber);

initializePayment();
