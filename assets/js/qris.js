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
    confirm: document.querySelector('#payment-confirm')
};

const params = new URLSearchParams(window.location.search);
const orderCode = params.get('kode');

let countdownDeadline = null;
let countdownInterval = null;
let pollingInterval = null;
let isProcessing = false;

function showError(title, message) {
    elements.loading.hidden = true;
    elements.content.hidden = true;
    elements.success.hidden = true;

    elements.errorTitle.textContent = title;
    elements.errorMessage.textContent = message;
    elements.error.hidden = false;

    stopTimers();
}

function showSuccess(code) {
    elements.loading.hidden = true;
    elements.content.hidden = true;
    elements.error.hidden = true;

    elements.successCode.textContent = `Nomor pesanan: ${code}`;
    elements.success.hidden = false;

    stopTimers();
}

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
    if (countdownInterval) {
        clearInterval(countdownInterval);
    }

    countdownDeadline = Date.now() + Math.max(0, seconds) * 1000;

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

            showMessage('Waktu pembayaran telah habis. Memeriksa status pesanan...');
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
    try {
        const response = await postJSON('payment-status.php', {
            kode_pesanan: orderCode
        });

        if (!response.httpOk || response.data.success !== true) {
            showMessage(
                response.data.message || 'Gagal memeriksa status pembayaran.'
            );
            return;
        }

        const data = response.data;
        if (data.metode_pembayaran !== 'QRIS') {
            showError(
                'Metode Pembayaran Tidak Sesuai',
                'Halaman ini hanya digunakan untuk pembayaran QRIS.'
            );
            return;
        }

        if (handlePaymentStatus(data)) return;

        elements.total.textContent = formatRupiah(
            Number(data.total_harga)
        );

        if (data.sisa_detik !== undefined) {
            if (Number(data.sisa_detik) <= 0) {
                showMessage('Waktu pembayaran habis. Memeriksa status pesanan...');
                return;
            }

            startCountdown(Number(data.sisa_detik));
        }

        elements.loading.hidden = true;
        elements.content.hidden = false;
    } catch (error) {
        showMessage('Tidak dapat terhubung ke server. Periksa koneksi lalu coba lagi.');
    }
}

async function confirmPayment() {
    if (isProcessing) return;

    isProcessing = true;
    elements.confirm.disabled = true;
    elements.confirm.textContent = 'Memproses Konfirmasi...';
    elements.message.hidden = true;

    try {
        const response = await postJSON('payment-confirm.php', {
            kode_pesanan: orderCode
        });

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

        if (response.httpOk === false && response.data.message) {
            showMessage(response.data.message);
        } else {
            showMessage(
                response.data.message || 'Pembayaran gagal dikonfirmasi.'
            );
        }
    } catch (error) {
        showMessage('Terjadi kesalahan saat mengonfirmasi pembayaran.');
    } finally {
        isProcessing = false;

        if (!elements.success.hidden || !elements.error.hidden) {
            return;
        }

        elements.confirm.disabled = false;
        elements.confirm.textContent = 'Simulasikan Pembayaran Berhasil';
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

    if (!elements.content.hidden) {
        pollingInterval = setInterval(checkPaymentStatus, 5000);
    }
}

elements.confirm.addEventListener('click', confirmPayment);

initializePayment();
