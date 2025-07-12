<?php
// upload.php
header('Content-Type: application/json');
// Biarkan CORS untuk akses dari mana saja (dalam pengembangan, untuk produksi atur dengan benar)
header("Access-Control-Allow-Origin: *");
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Dapatkan ukuran data yang diterima
    $size = $_SERVER['CONTENT_LENGTH'];
    echo json_encode(['size' => $size]);
} else {
    echo json_encode(['error' => 'Metode tidak diizinkan']);
}
?>
Namun, perhatikan bahwa `$_SERVER['CONTENT_LENGTH']` mungkin tidak akurat jika ada encoding. Alternatif: `file_get_contents('php://input')` dan hitung panjangnya, tapi itu boros memori. Kita hanya butuh ukurannya.
Tapi `CONTENT_LENGTH` adalah ukuran dalam byte yang dikirim oleh client, jadi itu sudah cukup.
Kembali ke JavaScript:
script.js:
const startBtn = document.getElementById('start-btn');
const pingValue = document.getElementById('ping-value');
const downloadValue = document.getElementById('download-value');
const uploadValue = document.getElementById('upload-value');
const gaugeInner = document.querySelector('.gauge-inner');
// URL untuk file download (ganti dengan URL file yang ada di server)
const downloadFiles = [
    'random1MB.dat',
    'random5MB.dat',
    'random10MB.dat'
];
// URL untuk upload (ganti sesuai)
const uploadUrl = 'upload.php';
// Ukuran file untuk download (dalam byte)
const fileSizes = {
    'random1MB.dat': 1000000, // 1MB
    'random5MB.dat': 5000000, // 5MB
    'random10MB.dat': 10000000 // 10MB
};
// Fungsi untuk mengukur ping
async function measurePing() {
    const start = performance.now();
    await fetch(downloadFiles[0] + '?cache=' + Date.now(), {
        method: 'HEAD',
        cache: 'no-cache'
    });
    const end = performance.now();
    return end - start; // dalam milidetik
}
// Fungsi untuk mengukur download
async function measureDownload() {
    let totalSpeed = 0;
    let count = 0;
    for (const file of downloadFiles) {
        const fileSize = fileSizes[file]; // dalam byte
        const start = performance.now();
        await fetch(file + '?cache=' + Date.now(), {
            cache: 'no-cache'
        });
        const end = performance.now();
        const duration = (end - start) / 1000; // dalam detik
        const speed = (fileSize * 8) / duration / 1000000; // dalam Mbps
        totalSpeed += speed;
        count++;
    }
    return totalSpeed / count; // rata-rata
}
// Fungsi untuk mengukur upload
async function measureUpload() {
    let totalSpeed = 0;
    let count = 0;
    // Ukuran data untuk diunggah (dalam byte): 1MB, 5MB, 10MB
    const uploadSizes = [1000000, 5000000, 10000000];
    for (const size of uploadSizes) {
        const blob = new Blob([new ArrayBuffer(size)]); // blob dengan ukuran yang ditentukan
        const formData = new FormData();
        formData.append('file', blob);
        const start = performance.now();
        const response = await fetch(uploadUrl, {
            method: 'POST',
            body: formData
        });
        const end = performance.now();
        const duration = (end - start) / 1000; // dalam detik
        const speed = (size * 8) / duration / 1000000; // dalam Mbps
        totalSpeed += speed;
        count++;
    }
    return totalSpeed / count; // rata-rata
}
// Fungsi untuk memperbarui UI
function updateUI(progress, ping, download, upload) {
    // Set gauge progress (lingkaran)
    gaugeInner.style.setProperty('--progress', progress + '%');
    // Update nilai
    if (ping !== null) {
        pingValue.textContent = ping.toFixed(2);
    }
    if (download !== null) {
        downloadValue.textContent = download.toFixed(2);
    }
    if (upload !== null) {
        uploadValue.textContent = upload.toFixed(2);
    }
}
// Fungsi utama untuk menjalankan speedtest
async function runSpeedTest() {
    startBtn.disabled = true;
    startBtn.textContent = 'Testing...';
    let ping = null;
    let download = null;
    let upload = null;
    try {
        // Update progress
        updateUI(0, ping, download, upload);
        // Langkah 1: Ping
        ping = await measurePing();
        updateUI(33, ping, download, upload);
        // Langkah 2: Download
        download = await measureDownload();
        updateUI(66, ping, download, upload);
        // Langkah 3: Upload
        upload = await measureUpload();
        updateUI(100, ping, download, upload);
    } catch (error) {
        console.error('Error during speed test:', error);
        alert('Terjadi kesalahan saat melakukan tes. Silakan coba lagi.');
    } finally {
        startBtn.disabled = false;
        startBtn.textContent = 'GO';
    }
}
// Event listener untuk tombol
startBtn.addEventListener('click', runSpeedTest);