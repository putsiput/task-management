CREATE TABLE IF NOT EXISTS tugas (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    tanggal DATE NOT NULL,
    divisi TEXT NOT NULL,
    deskripsi TEXT NOT NULL,
    status TEXT DEFAULT 'Belum Selesai',
    catatan TEXT
);