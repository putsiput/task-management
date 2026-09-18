<?php
// ==========================================
// CONFIGURATION & DATABASE AUTO-SETUP
// ==========================================
$db_file = __DIR__ . '/db_kinerja.sqlite';
$pdo = new PDO("sqlite:" . $db_file);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Buat Tabel jika belum ada
$pdo->exec("CREATE TABLE IF NOT EXISTS tugas (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    tanggal DATE NOT NULL,
    divisi TEXT NOT NULL,
    deskripsi TEXT NOT NULL,
    status TEXT DEFAULT 'Belum Selesai',
    catatan TEXT
)");

// Handle POST Request (Tambah, Edit, Update Status, Hapus)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && $_POST['action'] === 'tambah') {
        $stmt = $pdo->prepare("INSERT INTO tugas (tanggal, divisi, deskripsi, status, catatan) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$_POST['tanggal'], $_POST['divisi'], $_POST['deskripsi'], $_POST['status'], $_POST['catatan']]);
        header("Location: index.php?bulan=" . date('Y-m', strtotime($_POST['tanggal'])));
        exit;
    }
    if (isset($_POST['action']) && $_POST['action'] === 'edit') {
        $stmt = $pdo->prepare("UPDATE tugas SET tanggal = ?, divisi = ?, deskripsi = ?, status = ?, catatan = ? WHERE id = ?");
        $stmt->execute([$_POST['tanggal'], $_POST['divisi'], $_POST['deskripsi'], $_POST['status'], $_POST['catatan'], $_POST['id']]);
        header("Location: index.php?bulan=" . ($_GET['bulan'] ?? date('Y-m')));
        exit;
    }
    if (isset($_POST['action']) && $_POST['action'] === 'update_status') {
        $stmt = $pdo->prepare("UPDATE tugas SET status = ? WHERE id = ?");
        $stmt->execute([$_POST['status'], $_POST['id']]);
        header("Location: index.php?bulan=" . ($_GET['bulan'] ?? date('Y-m')));
        exit;
    }
    if (isset($_POST['action']) && $_POST['action'] === 'hapus') {
        $stmt = $pdo->prepare("DELETE FROM tugas WHERE id = ?");
        $stmt->execute([$_POST['id']]);
        header("Location: index.php?bulan=" . ($_GET['bulan'] ?? date('Y-m')));
        exit;
    }
}

// Filter Bulan
$selected_month = $_GET['bulan'] ?? date('Y-m');

// Stat & Rekap Data
$stmt = $pdo->prepare("SELECT 
    COUNT(*) as total,
    SUM(CASE WHEN status = 'Selesai' THEN 1 ELSE 0 END) as selesai,
    SUM(CASE WHEN status = 'Proses' THEN 1 ELSE 0 END) as proses,
    SUM(CASE WHEN status = 'Belum Selesai' THEN 1 ELSE 0 END) as belum
    FROM tugas WHERE strftime('%Y-%m', tanggal) = ?");
$stmt->execute([$selected_month]);
$stats = $stmt->fetch(PDO::FETCH_ASSOC);

$total = $stats['total'] ?: 0;
$selesai = $stats['selesai'] ?: 0;
$proses = $stats['proses'] ?: 0;
$belum = $stats['belum'] ?: 0;
$percentage = $total > 0 ? round(($selesai / $total) * 100) : 0;

// Ambil Daftar Tugas
$stmt = $pdo->prepare("SELECT * FROM tugas WHERE strftime('%Y-%m', tanggal) = ? ORDER BY tanggal DESC, id DESC");
$stmt->execute([$selected_month]);
$tasks = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Logika Kesimpulan Kinerja Akhir Bulan
function generateKesimpulan($percentage, $total, $belum) {
    if ($total == 0) return "Belum ada catatan pekerjaan pada bulan ini.";
    if ($percentage >= 90) {
        return "<strong>Sangat Baik:</strong> Kinerja bulan ini luar biasa! Sebagian besar tugas ($percentage%) telah diselesaikan dengan sangat efisien.";
    } elseif ($percentage >= 75) {
        return "<strong>Baik:</strong> Kinerja bulan ini cukup solid ($percentage% selesai). Masih terdapat $belum tugas yang perlu dituntaskan.";
    } elseif ($percentage >= 50) {
        return "<strong>Cukup:</strong> Capaian kinerja mencapai $percentage%. Disarankan untuk memprioritaskan tugas-tugas yang masih tertunda.";
    } else {
        return "<strong>Perlu Peningkatan:</strong> Banyak tugas yang belum selesai ($belum dari $total tugas). Perlu evaluasi beban kerja harian.";
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kinbot - Laporan Kinerja Harian (<?= date('F Y', strtotime($selected_month)) ?>)</title>
    <!-- Font Poppins -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        /* Variable Warna - Default Dark Mode */
        :root {
            --bg-body: #0F172A;
            --bg-card: #1E293B;
            --bg-input: #0F172A;
            --text-main: #FFFFFF;
            --text-muted: #94A3B8;
            --border-color: #334155;
            --biru-muda: #38BDF8;
            --gold: #D4AF37;
            --table-header-bg: #0F172A;
            
            /* Warna Tombol Simpan Tugas (Dark Mode) */
            --btn-simpan-bg: #D4AF37;
            --btn-simpan-text: #0F172A;
        }

        /* Variable Warna - Light Mode Override */
        [data-theme="light"] {
            --bg-body: #F8FAFC;
            --bg-card: #FFFFFF;
            --bg-input: #F1F5F9;
            --text-main: #0F172A;
            --text-muted: #64748B;
            --border-color: #E2E8F0;
            --biru-muda: #0284C7;
            --gold: #B45309;
            --table-header-bg: #F1F5F9;
            
            /* Warna Tombol Simpan Tugas (Light Mode) */
            --btn-simpan-bg: #0F172A;
            --btn-simpan-text: #FFFFFF;
        }

        * { box-sizing: border-box; font-family: 'Poppins', sans-serif; transition: background-color 0.3s ease, color 0.3s ease, border-color 0.3s ease; }
        body { background-color: var(--bg-body); color: var(--text-main); margin: 0; padding: 20px; }
        .container { max-width: 1150px; margin: 0 auto; }
        
        /* Header */
        header { display: flex; justify-content: space-between; align-items: center; border-bottom: 2px solid var(--gold); padding-bottom: 15px; margin-bottom: 25px; gap: 15px; flex-wrap: wrap; }
        .header-brand { display: flex; align-items: center; gap: 12px; }
        .header-mascot { width: 52px; height: 52px; flex-shrink: 0; }
        header h1 { color: var(--biru-muda); margin: 0; font-size: 24px; display: flex; align-items: center; gap: 8px; }
        header p { margin: 0; color: var(--text-muted); font-size: 14px; }
        .header-controls { display: flex; align-items: center; gap: 12px; }

        /* Buttons & Controls */
        .btn {
            background-color: var(--bg-card);
            border: 1px solid var(--border-color);
            color: var(--text-main);
            padding: 8px 14px;
            border-radius: 6px;
            cursor: pointer;
            font-size: 13px;
            font-weight: 500;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            text-decoration: none;
        }
        .btn:hover { border-color: var(--gold); color: var(--gold); }
        .btn-excel { border-color: #16A34A; color: #16A34A; }
        .btn-excel:hover { background-color: #16A34A; color: #FFFFFF; }
        .btn-pdf { border-color: #DC2626; color: #DC2626; }
        .btn-pdf:hover { background-color: #DC2626; color: #FFFFFF; }
        
        /* Custom Button Simpan Tugas Dynamic Color */
        button.btn-primary { 
            background-color: var(--btn-simpan-bg); 
            color: var(--btn-simpan-text); 
            border: none; 
            padding: 10px 20px; 
            border-radius: 6px; 
            font-weight: 600; 
            cursor: pointer; 
        }
        button.btn-primary:hover { opacity: 0.9; }

        /* Small Action Buttons Inline */
        .btn-sm { padding: 5px 10px; font-size: 11px; border-radius: 4px; border: none; cursor: pointer; font-weight: 600; white-space: nowrap; display: inline-flex; align-items: center; gap: 4px; }
        .btn-edit { background-color: #0284C7; color: white; }
        .btn-edit:hover { background-color: #0369A1; }
        .btn-delete { background-color: #DC2626; color: white; }
        .btn-delete:hover { background-color: #B91C1C; }

        /* Dashboard Grid */
        .grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 15px; margin-bottom: 25px; }
        .card { background-color: var(--bg-card); border-radius: 10px; padding: 18px; border: 1px solid var(--border-color); }
        .card h3 { font-size: 13px; color: var(--text-muted); margin: 0 0 8px 0; text-transform: uppercase; }
        .card .value { font-size: 28px; font-weight: 700; color: var(--text-main); }
        .card.gold-card { border-left: 4px solid var(--gold); }
        .card.blue-card { border-left: 4px solid var(--biru-muda); }

        /* Progress Bar */
        .progress-section { background-color: var(--bg-card); border-radius: 10px; padding: 20px; margin-bottom: 25px; border: 1px solid var(--border-color); }
        .progress-header { display: flex; justify-content: space-between; margin-bottom: 10px; }
        .progress-title { font-weight: 600; color: var(--gold); }
        .progress-bar-bg { background-color: var(--bg-input); height: 18px; border-radius: 10px; overflow: hidden; border: 1px solid var(--border-color); }
        .progress-bar-fill { background: linear-gradient(90deg, #38BDF8, #D4AF37); height: 100%; transition: width 0.5s ease; }

        /* Toolbar Export */
        .export-toolbar { display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px; flex-wrap: wrap; gap: 10px; }
        .export-buttons { display: flex; gap: 8px; }

        /* Form & Filter */
        .form-section { background-color: var(--bg-card); border-radius: 10px; padding: 20px; margin-bottom: 25px; border: 1px solid var(--border-color); }
        .form-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 12px; margin-bottom: 12px; }
        input, select, textarea { width: 100%; background: var(--bg-input); border: 1px solid var(--border-color); color: var(--text-main); padding: 10px; border-radius: 6px; font-size: 14px; }
        input:focus, select:focus, textarea:focus { outline: none; border-color: var(--biru-muda); }

        /* Table Layout (Sejajar & Proporsional) */
        .table-responsive { width: 100%; overflow-x: auto; border-radius: 10px; border: 1px solid var(--border-color); margin-bottom: 25px; }
        table { width: 100%; border-collapse: collapse; background-color: var(--bg-card); table-layout: fixed; }
        th, td { padding: 12px 10px; text-align: left; border-bottom: 1px solid var(--border-color); font-size: 13px; vertical-align: middle; word-wrap: break-word; white-space: normal; }
        th { background-color: var(--table-header-bg); color: var(--gold); font-weight: 600; }
        
        /* Lebar Kolom */
        .col-tanggal { width: 10%; }
        .col-divisi  { width: 13%; }
        .col-desk    { width: 28%; }
        .col-status  { width: 11%; }
        .col-catatan { width: 16%; }
        .col-aksi    { width: 22%; text-align: center; }

        /* Flexbox Khusus Kolom Aksi Sejajar Horizontal */
        .action-flex-container {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            flex-wrap: nowrap;
        }

        .badge { padding: 4px 8px; border-radius: 12px; font-size: 11px; font-weight: 600; display: inline-block; white-space: nowrap; }
        .badge-pending { background: #475569; color: white; }
        .badge-proses { background: #0284C7; color: white; }
        .badge-selesai { background: #16A34A; color: white; }
        .badge-divisi { background: var(--bg-input); color: var(--biru-muda); border: 1px solid var(--biru-muda); }

        /* Kesimpulan Box dengan Mascot Integration */
        .summary-box { 
            background: var(--bg-card); 
            border: 1px solid var(--gold); 
            border-radius: 10px; 
            padding: 20px; 
            margin-bottom: 25px; 
            display: flex;
            align-items: center;
            gap: 20px;
        }
        .summary-mascot { width: 90px; height: 90px; flex-shrink: 0; }
        .summary-content { flex-grow: 1; }
        .summary-box h3 { color: var(--gold); margin-top: 0; margin-bottom: 8px; }

        /* Modal Edit CSS */
        .modal { display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.6); align-items: center; justify-content: center; }
        .modal-content { background-color: var(--bg-card); border: 1px solid var(--border-color); padding: 25px; border-radius: 10px; width: 100%; max-width: 500px; box-shadow: 0 10px 25px rgba(0,0,0,0.5); }

        /* Judul Khusus Cetak/Print (Hanya Muncul Saat Print) */
        .print-only-header { display: none; }

        /* PRINT STYLESHEET */
        @media print {
            body { background-color: #FFFFFF !important; color: #000000 !important; padding: 10px !important; }
            .container { max-width: 100% !important; margin: 0 !important; }
            
            header, .grid, .progress-section, .form-section, .export-toolbar, .btn-action-col, select, .modal, .summary-mascot { display: none !important; }
            
            .print-only-header { 
                display: block !important; 
                text-align: center; 
                margin-bottom: 20px; 
                border-bottom: 2px solid #000000; 
                padding-bottom: 10px; 
            }
            .print-only-header h2 { margin: 0; font-size: 20px; color: #000000; }
            .print-only-header p { margin: 5px 0 0 0; font-size: 12px; color: #333333; }

            .table-responsive { border: none; overflow: visible; }
            table { table-layout: auto; width: 100%; }
            .summary-box, table { background-color: #FFFFFF !important; color: #000000 !important; border: 1px solid #000000 !important; }
            th { background-color: #F1F5F9 !important; color: #000000 !important; border: 1px solid #000000 !important; }
            td { color: #000000 !important; border: 1px solid #000000 !important; padding: 8px !important; }
            .summary-box h3 { color: #000000 !important; }
            .badge { border: 1px solid #000 !important; color: #000 !important; background: transparent !important; }
        }
    </style>
</head>
<body>

<div class="container">
    <!-- Judul Cetak Khusus (Hanya Muncul di Print/PDF) -->
    <div class="print-only-header">
        <h2>LAPORAN KINERJA PEKERJAAN</h2>
        <p>Periode: <?= date('F Y', strtotime($selected_month)) ?> | Unit: Admin BAAK & UPT TIK</p>
    </div>

    <!-- Header -->
    <header>
        <div class="header-brand">
            <!-- Kinbot SVG Mascot (Mini Header) -->
            <div class="header-mascot">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 400 400" width="100%" height="100%">
                  <defs>
                    <radialGradient id="bgGradH" cx="50%" cy="50%" r="50%">
                      <stop offset="0%" stop-color="#1E293B" />
                      <stop offset="100%" stop-color="#0F172A" />
                    </radialGradient>
                    <linearGradient id="bodyGradH" x1="0%" y1="0%" x2="100%" y2="100%">
                      <stop offset="0%" stop-color="#FFFFFF" />
                      <stop offset="100%" stop-color="#E2E8F0" />
                    </linearGradient>
                    <linearGradient id="accentBlueH" x1="0%" y1="0%" x2="0%" y2="100%">
                      <stop offset="0%" stop-color="#38BDF8" />
                      <stop offset="100%" stop-color="#0284C7" />
                    </linearGradient>
                    <linearGradient id="accentGoldH" x1="0%" y1="0%" x2="100%" y2="100%">
                      <stop offset="0%" stop-color="#FDE047" />
                      <stop offset="100%" stop-color="#D4AF37" />
                    </linearGradient>
                  </defs>
                  <circle cx="200" cy="200" r="190" fill="url(#bgGradH)" stroke="#38BDF8" stroke-width="8" />
                  <g id="kino-head-mini">
                    <path d="M 135 125 L 110 65 Q 135 60 165 95 Z" fill="url(#bodyGradH)" />
                    <path d="M 138 115 L 120 75 Q 135 72 158 98 Z" fill="url(#accentBlueH)" />
                    <path d="M 265 125 L 290 65 Q 265 60 235 95 Z" fill="url(#bodyGradH)" />
                    <path d="M 262 115 L 280 75 Q 265 72 242 98 Z" fill="url(#accentBlueH)" />
                    <rect x="195" y="60" width="10" height="30" rx="5" fill="#94A3B8" />
                    <circle cx="200" cy="52" r="10" fill="url(#accentGoldH)" />
                    <rect x="110" y="85" width="180" height="140" rx="65" fill="url(#bodyGradH)" />
                    <rect x="125" y="100" width="150" height="105" rx="48" fill="#0F172A" />
                    <path d="M 152 148 Q 167 130 182 148" fill="none" stroke="#38BDF8" stroke-width="8" stroke-linecap="round" />
                    <path d="M 218 148 Q 233 130 248 148" fill="none" stroke="#38BDF8" stroke-width="8" stroke-linecap="round" />
                    <ellipse cx="148" cy="168" rx="10" ry="5" fill="#FDE047" opacity="0.7" />
                    <ellipse cx="252" cy="168" rx="10" ry="5" fill="#FDE047" opacity="0.7" />
                    <polygon points="200,158 196,163 204,163" fill="#38BDF8" />
                    <path d="M 192 168 Q 200 176 200 168 Q 200 176 208 168" fill="none" stroke="#38BDF8" stroke-width="3" stroke-linecap="round" />
                  </g>
                </svg>
            </div>
            <div>
                <h1>Kinbot</h1>
                <p>Sistem Pengawasan Tugas Lokal (Admin BAAK & UPT TIK)</p>
            </div>
        </div>
        <div class="header-controls">
            <!-- Toggle Mode Gelap / Terang -->
            <button class="btn" id="themeToggle" onclick="toggleTheme()">
                <span id="themeIcon">🌙</span> <span id="themeText">Dark</span>
            </button>
            <!-- Filter Bulan -->
            <form method="GET" action="">
                <input type="month" name="bulan" value="<?= htmlspecialchars($selected_month) ?>" onchange="this.form.submit()">
            </form>
        </div>
    </header>

    <!-- Stat Cards -->
    <div class="grid">
        <div class="card blue-card">
            <h3>Total Pekerjaan</h3>
            <div class="value"><?= $total ?></div>
        </div>
        <div class="card">
            <h3>Selesai</h3>
            <div class="value" style="color: #16A34A;"><?= $selesai ?></div>
        </div>
        <div class="card">
            <h3>Dalam Proses</h3>
            <div class="value" style="color: #0284C7;"><?= $proses ?></div>
        </div>
        <div class="card gold-card">
            <h3>Belum Dikerjakan</h3>
            <div class="value" style="color: #DC2626;"><?= $belum ?></div>
        </div>
    </div>

    <!-- Progress Bar Visual -->
    <div class="progress-section">
        <div class="progress-header">
            <span class="progress-title">Progress Capaian Bulan Ini (<?= date('F Y', strtotime($selected_month)) ?>)</span>
            <span style="font-weight: bold; color: var(--biru-muda);"><?= $percentage ?>%</span>
        </div>
        <div class="progress-bar-bg">
            <div class="progress-bar-fill" style="width: <?= $percentage ?>%;"></div>
        </div>
    </div>

    <!-- Kesimpulan Akhir Bulan dengan Mascot Kinbot -->
    <div class="summary-box">
        <div class="summary-mascot">
            <!-- Kinbot Full SVG Mascot -->
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 400 400" width="100%" height="100%">
              <defs>
                <radialGradient id="bgGrad" cx="50%" cy="50%" r="50%">
                  <stop offset="0%" stop-color="#1E293B" />
                  <stop offset="100%" stop-color="#0F172A" />
                </radialGradient>
                <filter id="glowBlue" x="-20%" y="-20%" width="140%" height="140%">
                  <feGaussianBlur stdDeviation="6" result="blur" />
                  <feComposite in="SourceGraphic" in2="blur" operator="over" />
                </filter>
                <filter id="glowGold" x="-20%" y="-20%" width="140%" height="140%">
                  <feGaussianBlur stdDeviation="4" result="blur" />
                  <feComposite in="SourceGraphic" in2="blur" operator="over" />
                </filter>
                <linearGradient id="bodyGrad" x1="0%" y1="0%" x2="100%" y2="100%">
                  <stop offset="0%" stop-color="#FFFFFF" />
                  <stop offset="100%" stop-color="#E2E8F0" />
                </linearGradient>
                <linearGradient id="accentBlue" x1="0%" y1="0%" x2="0%" y2="100%">
                  <stop offset="0%" stop-color="#38BDF8" />
                  <stop offset="100%" stop-color="#0284C7" />
                </linearGradient>
                <linearGradient id="accentGold" x1="0%" y1="0%" x2="100%" y2="100%">
                  <stop offset="0%" stop-color="#FDE047" />
                  <stop offset="100%" stop-color="#D4AF37" />
                </linearGradient>
              </defs>
              <rect width="400" height="400" rx="30" fill="url(#bgGrad)" />
              <ellipse cx="200" cy="340" rx="75" ry="12" fill="#020617" opacity="0.6" />
              <g id="kino-mascot">
                <path d="M 250 270 Q 290 280 285 240 Q 280 210 300 200" fill="none" stroke="#94A3B8" stroke-width="8" stroke-linecap="round" />
                <circle cx="300" cy="200" r="10" fill="url(#accentGold)" filter="url(#glowGold)" />
                <ellipse cx="160" cy="315" rx="20" ry="14" fill="#CBD5E1" />
                <ellipse cx="240" cy="315" rx="20" ry="14" fill="#CBD5E1" />
                <rect x="140" y="210" width="120" height="105" rx="50" fill="url(#bodyGrad)" />
                <rect x="170" y="240" width="60" height="35" rx="12" fill="#0F172A" />
                <path d="M 180 258 L 192 258 L 197 248 L 203 268 L 208 254 L 213 258 L 220 258" fill="none" stroke="#38BDF8" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" filter="url(#glowBlue)" />
                <g>
                  <path d="M 142 240 Q 110 220 115 195 Q 128 190 138 215 Z" fill="url(#bodyGrad)" />
                  <circle cx="118" cy="196" r="8" fill="#38BDF8" filter="url(#glowBlue)" />
                </g>
                <path d="M 258 240 Q 285 255 270 275 Q 255 280 250 260 Z" fill="url(#bodyGrad)" />
                <path d="M 135 125 L 110 65 Q 135 60 165 95 Z" fill="url(#bodyGrad)" />
                <path d="M 138 115 L 120 75 Q 135 72 158 98 Z" fill="url(#accentBlue)" filter="url(#glowBlue)" />
                <path d="M 265 125 L 290 65 Q 265 60 235 95 Z" fill="url(#bodyGrad)" />
                <path d="M 262 115 L 280 75 Q 265 72 242 98 Z" fill="url(#accentBlue)" filter="url(#glowBlue)" />
                <rect x="195" y="60" width="10" height="30" rx="5" fill="#94A3B8" />
                <circle cx="200" cy="52" r="10" fill="url(#accentGold)" filter="url(#glowGold)" />
                <rect x="110" y="85" width="180" height="140" rx="65" fill="url(#bodyGrad)" />
                <rect x="125" y="100" width="150" height="105" rx="48" fill="#0F172A" />
                <path d="M 152 148 Q 167 130 182 148" fill="none" stroke="#38BDF8" stroke-width="8" stroke-linecap="round" filter="url(#glowBlue)" />
                <path d="M 218 148 Q 233 130 248 148" fill="none" stroke="#38BDF8" stroke-width="8" stroke-linecap="round" filter="url(#glowBlue)" />
                <ellipse cx="148" cy="168" rx="10" ry="5" fill="#FDE047" opacity="0.7" filter="url(#glowGold)" />
                <ellipse cx="252" cy="168" rx="10" ry="5" fill="#FDE047" opacity="0.7" filter="url(#glowGold)" />
                <line x1="130" y1="155" x2="112" y2="150" stroke="#38BDF8" stroke-width="3" stroke-linecap="round" opacity="0.8" />
                <line x1="130" y1="165" x2="115" y2="168" stroke="#38BDF8" stroke-width="3" stroke-linecap="round" opacity="0.8" />
                <line x1="270" y1="155" x2="288" y2="150" stroke="#38BDF8" stroke-width="3" stroke-linecap="round" opacity="0.8" />
                <line x1="270" y1="165" x2="285" y2="168" stroke="#38BDF8" stroke-width="3" stroke-linecap="round" opacity="0.8" />
                <polygon points="200,158 196,163 204,163" fill="#38BDF8" />
                <path d="M 192 168 Q 200 176 200 168 Q 200 176 208 168" fill="none" stroke="#38BDF8" stroke-width="3" stroke-linecap="round" />
              </g>
            </svg>
        </div>
        <div class="summary-content">
            <h3>Catatan Kino (Kesimpulan Akhir Bulan)</h3>
            <div><?= generateKesimpulan($percentage, $total, $belum) ?></div>
        </div>
    </div>

    <!-- Form Input Tugas -->
    <div class="form-section">
        <h3 style="color: var(--biru-muda); margin-top:0;">Tambah Catatan Pekerjaan Baru</h3>
        <form method="POST" action="">
            <input type="hidden" name="action" value="tambah">
            <div class="form-grid">
                <div>
                    <label style="font-size: 12px; color: var(--text-muted);">Tanggal</label>
                    <input type="date" name="tanggal" value="<?= date('Y-m-d') ?>" required>
                </div>
                <div>
                    <label style="font-size: 12px; color: var(--text-muted);">Divisi / Unit</label>
                    <select name="divisi" required>
                        <option value="Admin BAAK">Admin BAAK</option>
                        <option value="UPT TIK">UPT TIK</option>
                        <!-- <option value="UPT TIK">UPT TIK</option> --> <!-- nantinya untuk penambahan tugas divisi lain -->
                    </select>
                </div>
                <div>
                    <label style="font-size: 12px; color: var(--text-muted);">Status Awal</label>
                    <select name="status">
                        <option value="Belum Selesai">Belum Selesai</option>
                        <option value="Proses">Proses</option>
                        <option value="Selesai">Selesai</option>
                    </select>
                </div>
            </div>
            <div style="margin-bottom: 12px;">
                <input type="text" name="deskripsi" placeholder="Deskripsi Pekerjaan / Tugas..." required>
            </div>
            <div style="margin-bottom: 12px;">
                <input type="text" name="catatan" placeholder="Catatan tambahan (Opsional)">
            </div>
            <button type="submit" class="btn-primary">+ Simpan Tugas</button>
        </form>
    </div>

    <!-- Export Toolbar & Action Buttons -->
    <div class="export-toolbar">
        <h3 style="margin: 0; color: var(--text-main); font-size: 16px;">Daftar Pekerjaan</h3>
        <div class="export-buttons">
            <button onclick="window.print()" class="btn">🖨️ Print Preview / Cetak Laporan</button>
            <button onclick="exportToPDF()" class="btn btn-pdf">📄 Export to PDF</button>
            <button onclick="exportToExcel('laporan-kinerja-<?= $selected_month ?>')" class="btn btn-excel">📊 Export to Excel</button>
        </div>
    </div>

    <!-- Tabel Daftar Pekerjaan -->
    <div class="table-responsive">
        <table id="laporanTable">
            <thead>
                <tr>
                    <th class="col-tanggal">Tanggal</th>
                    <th class="col-divisi">Divisi</th>
                    <th class="col-desk">Deskripsi Pekerjaan</th>
                    <th class="col-status">Status</th>
                    <th class="col-catatan">Catatan</th>
                    <th class="col-aksi btn-action-col">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($tasks)): ?>
                <tr>
                    <td colspan="6" style="text-align: center; color: var(--text-muted);">Belum ada data pekerjaan untuk bulan ini.</td>
                </tr>
                <?php else: ?>
                    <?php foreach ($tasks as $task): ?>
                    <tr>
                        <td class="col-tanggal"><?= date('d/m/Y', strtotime($task['tanggal'])) ?></td>
                        <td class="col-divisi"><span class="badge badge-divisi"><?= htmlspecialchars($task['divisi']) ?></span></td>
                        <td class="col-desk"><?= htmlspecialchars($task['deskripsi']) ?></td>
                        <td class="col-status">
                            <?php 
                                $badge = 'badge-pending';
                                if ($task['status'] === 'Proses') $badge = 'badge-proses';
                                if ($task['status'] === 'Selesai') $badge = 'badge-selesai';
                            ?>
                            <span class="badge <?= $badge ?>"><?= $task['status'] ?></span>
                        </td>
                        <td class="col-catatan" style="color: var(--text-muted);"><?= htmlspecialchars($task['catatan'] ?: '-') ?></td>
                        <td class="col-aksi btn-action-col">
                            <div class="action-flex-container">
                                <!-- Quick Change Status -->
                                <form method="POST" style="display:inline-block; margin:0;">
                                    <input type="hidden" name="action" value="update_status">
                                    <input type="hidden" name="id" value="<?= $task['id'] ?>">
                                    <select name="status" onchange="this.form.submit()" style="padding: 3px 4px; font-size: 11px; width: auto; margin:0;">
                                        <option value="Belum Selesai" <?= $task['status'] === 'Belum Selesai' ? 'selected' : '' ?>>Belum Selesai</option>
                                        <option value="Proses" <?= $task['status'] === 'Proses' ? 'selected' : '' ?>>Proses</option>
                                        <option value="Selesai" <?= $task['status'] === 'Selesai' ? 'selected' : '' ?>>Selesai</option>
                                    </select>
                                </form>

                                <!-- Tombol Ubah (Edit) -->
                                <button class="btn-sm btn-edit" onclick="openEditModal(<?= htmlspecialchars(json_encode($task)) ?>)">✏️ Ubah</button>

                                <!-- Tombol Hapus (Delete) -->
                                <form method="POST" style="display:inline-block; margin:0;" onsubmit="return confirm('Apakah Anda yakin ingin menghapus pekerjaan ini?');">
                                    <input type="hidden" name="action" value="hapus">
                                    <input type="hidden" name="id" value="<?= $task['id'] ?>">
                                    <button type="submit" class="btn-sm btn-delete">🗑️ Hapus</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal Dialog Ubah Data -->
<div id="editModal" class="modal">
    <div class="modal-content">
        <h3 style="margin-top:0; color: var(--biru-muda);">Ubah Data Pekerjaan</h3>
        <form method="POST" action="">
            <input type="hidden" name="action" value="edit">
            <input type="hidden" name="id" id="edit_id">
            
            <div style="margin-bottom: 10px;">
                <label style="font-size: 12px; color: var(--text-muted);">Tanggal</label>
                <input type="date" name="tanggal" id="edit_tanggal" required>
            </div>
            
            <div style="margin-bottom: 10px;">
                <label style="font-size: 12px; color: var(--text-muted);">Divisi</label>
                <select name="divisi" id="edit_divisi" required>
                    <option value="Admin BAAK">Admin BAAK</option>
                    <option value="UPT TIK">UPT TIK</option>
                </select>
            </div>

            <div style="margin-bottom: 10px;">
                <label style="font-size: 12px; color: var(--text-muted);">Status</label>
                <select name="status" id="edit_status" required>
                    <option value="Belum Selesai">Belum Selesai</option>
                    <option value="Proses">Proses</option>
                    <option value="Selesai">Selesai</option>
                </select>
            </div>

            <div style="margin-bottom: 10px;">
                <label style="font-size: 12px; color: var(--text-muted);">Deskripsi Pekerjaan</label>
                <input type="text" name="deskripsi" id="edit_deskripsi" required>
            </div>

            <div style="margin-bottom: 15px;">
                <label style="font-size: 12px; color: var(--text-muted);">Catatan</label>
                <input type="text" name="catatan" id="edit_catatan">
            </div>

            <div style="display: flex; justify-content: flex-end; gap: 8px;">
                <button type="button" class="btn" onclick="closeEditModal()">Batal</button>
                <button type="submit" class="btn-primary">Simpan Perubahan</button>
            </div>
        </form>
    </div>
</div>

<script>
    // Theme Switcher & System Persist
    const currentTheme = localStorage.getItem('theme') || 'dark';
    document.documentElement.setAttribute('data-theme', currentTheme);
    updateToggleUI(currentTheme);

    function toggleTheme() {
        let theme = document.documentElement.getAttribute('data-theme');
        let newTheme = theme === 'dark' ? 'light' : 'dark';
        
        document.documentElement.setAttribute('data-theme', newTheme);
        localStorage.setItem('theme', newTheme);
        updateToggleUI(newTheme);
    }

    function updateToggleUI(theme) {
        const icon = document.getElementById('themeIcon');
        const text = document.getElementById('themeText');
        if (theme === 'light') {
            icon.textContent = '☀️';
            text.textContent = 'Light';
        } else {
            icon.textContent = '🌙';
            text.textContent = 'Dark';
        }
    }

    // Modal Edit Operations
    function openEditModal(task) {
        document.getElementById('edit_id').value = task.id;
        document.getElementById('edit_tanggal').value = task.tanggal;
        document.getElementById('edit_divisi').value = task.divisi;
        document.getElementById('edit_status').value = task.status;
        document.getElementById('edit_deskripsi').value = task.deskripsi;
        document.getElementById('edit_catatan').value = task.catatan || '';
        document.getElementById('editModal').style.display = 'flex';
    }

    function closeEditModal() {
        document.getElementById('editModal').style.display = 'none';
    }

    // Export to Excel (Hanya Mengambil Tabel Laporan + Kesimpulan)
    function exportToExcel(filename = '') {
        const summaryText = document.querySelector('.summary-content').innerText;
        const table = document.getElementById("laporanTable").cloneNode(true);
        
        // Hapus kolom 'Aksi' sebelum diexport
        const rows = table.rows;
        for (let i = 0; i < rows.length; i++) {
            rows[i].deleteCell(-1);
        }

        const excelContent = `
            <h3>LAPORAN KINERJA PEKERJAAN HARIAN</h3>
            <div style="margin-bottom:15px; border:1px solid #ccc; padding:10px;">
                ${summaryText}
            </div>
            ${table.outerHTML}
        `;

        const blob = new Blob(['\ufeff' + excelContent], {
            type: 'application/vnd.ms-excel;charset=utf-8;'
        });

        const downloadLink = document.createElement("a");
        downloadLink.href = URL.createObjectURL(blob);
        downloadLink.download = filename ? filename + '.xls' : 'laporan_kinerja.xls';
        document.body.appendChild(downloadLink);
        downloadLink.click();
        document.body.removeChild(downloadLink);
    }

    // Export to PDF (Native Dialog Triggering Filtered View)
    function exportToPDF() {
        window.print();
    }
</script>

</body>
</html>