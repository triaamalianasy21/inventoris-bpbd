# Dokumentasi API (Session Cookie)

Semua endpoint memakai **session cookie** (PHPSESSID).  
Format respons standar:

```json
{ "ok": true, "data": ..., "error": null }
```

Jika gagal:
```json
{ "ok": false, "data": null, "error": "pesan error" }
```

Catatan:
- Login dulu lewat web / endpoint login, lalu **Android simpan cookie**.
- Content-Type JSON disarankan untuk request POST.

---

## 0) Login (`/api/login.php`)

### a) Login
`POST /api/login.php`

Body JSON:
```json
{
  "username": "admin",
  "password": "admin123"
}
```

Response:
```json
{ "ok": true, "data": { "id_user": 1, "nama": "Admin", "username": "admin", "role": "admin" }, "error": null }
```

Catatan: cookie `PHPSESSID` otomatis dibuat dan dipakai untuk request berikutnya.

### b) Cek Session
`GET /api/login.php?action=me`

### c) Logout
`POST /api/login.php?action=logout`

---

## 0.1) Register (`/api/register.php`)

`POST /api/register.php`

Body JSON:
```json
{
  "nama": "Nama Lengkap",
  "username": "pemohon_baru",
  "password": "rahasia123",
  "no_kontak": "08xxxxxxxxxx"
}
```

Catatan:
- Role otomatis `pemohon`.
- Password otomatis di-hash.

---
## 1) Peminjaman (`/api/peminjaman.php`)

### a) List
`GET /api/peminjaman.php?action=list`

Opsional:
- `include_items=1` (ikutkan detail items)

### b) Detail
`GET /api/peminjaman.php?action=detail&id={id}`

### c) Create
`POST /api/peminjaman.php?action=create`

Body JSON:
```json
{
  "nama_pihak1": "Nama Pemohon",
  "nip": "123",
  "jabatan": "Mahasiswa",
  "nama_pihak2": "BPBD",
  "nip1": "",
  "jabatan1": "",
  "alamat": "Alamat",
  "no_hp": "08xxx",
  "tujuan": "Kesemaptaan",
  "tgl_pinjam": "2026-01-10",
  "tgl_jatuh_tempo": "2026-01-12",
  "spesifikasi": "-",
  "keterangan": "Lengkap",
  "items": [
    { "barang_id": 1, "qty": 1 },
    { "barang_id": 2, "qty": 3 }
  ]
}
```

### d) Update
`POST /api/peminjaman.php?action=update`

Body JSON (minimal):
```json
{
  "id": 10,
  "nama_pihak2": "BPBD Banyumas",
  "tujuan": "Kesemaptaan",
  "items": [
    { "barang_id": 1, "qty": 1 }
  ]
}
```
Catatan: update akan **reset status jadi menunggu_verifikasi**.

### e) Delete
`POST /api/peminjaman.php?action=delete`
```json
{ "id": 10 }
```

---

## 2) Pengembalian (`/api/pengembalian.php`)

### a) List
`GET /api/pengembalian.php?action=list`

### b) Detail
`GET /api/pengembalian.php?action=detail&id={id}`

### c) Create
`POST /api/pengembalian.php?action=create`

Body JSON:
```json
{
  "id_pinjam": 10,
  "kondisi_kembali": "Baik dan lengkap",
  "tgl_kembali": "2026-01-12",
  "foto": {
    "1": "data:image/png;base64,....",
    "2": "data:image/jpeg;base64,...."
  }
}
```
Catatan:
- jika role pemohon, `id_pinjam` boleh kosong (auto ambil peminjaman approved terbaru).
- `foto` wajib untuk setiap barang (key = `id_barang`).
- Alternatif: kirim `multipart/form-data` dengan `foto[ID_BARANG]` (JPG/PNG, maks 10MB).

### d) Update
`POST /api/pengembalian.php?action=update`
```json
{
  "id": 5,
  "kondisi_kembali": "Baik",
  "tgl_kembali": "2026-01-12",
  "foto": {
    "1": "data:image/png;base64,...."
  }
}
```
Catatan: update akan **reset status jadi pending**.
### e) Delete
`POST /api/pengembalian.php?action=delete`
```json
{ "id": 5 }
```

---

## 3) Pakta Integritas (`/api/pakta.php`)

### a) List
`GET /api/pakta.php?action=list`

### b) Detail
`GET /api/pakta.php?action=detail&id={id}`

### c) Create
`POST /api/pakta.php?action=create`

Body JSON (field menyesuaikan kolom tabel):
```json
{
  "id_pinjam": 10,
  "nama": "Nama Pemohon",
  "nip_nik": "123",
  "jabatan": "Mahasiswa",
  "barang_digunakan": "Mobil"
}
```

### d) Update
`POST /api/pakta.php?action=update`
```json
{
  "id": 3,
  "jabatan": "Staf",
  "barang_digunakan": "Mobil"
}
```

### e) Delete
`POST /api/pakta.php?action=delete`
```json
{ "id": 3 }
```

---

## 4) Barang (`/api/barang.php`)

### a) List
`GET /api/barang.php?action=list`
Opsional:
- `q` (search)

### b) Detail
`GET /api/barang.php?action=detail&id={id}`

### c) Create (admin)
`POST /api/barang.php?action=create`

Body JSON: sesuai kolom di tabel `barang`
```json
{
  "nama_barang": "Mobil",
  "kode_barang": "1320xxx",
  "nomor_register": "1",
  "stok_barang": 1,
  "tgl_masuk": "2026-01-10",
  "status_brg": "menunggu_verifikasi"
}
```

### d) Update (admin)
`POST /api/barang.php?action=update`
```json
{
  "id": 4,
  "nama_barang": "Mobil"
}
```

### e) Delete (admin)
`POST /api/barang.php?action=delete`
```json
{ "id": 4 }
```

---

## 5) Notifikasi (`/api/notifikasi.php`)

### a) List
`GET /api/notifikasi.php?action=list`

Opsional:
- `unread=1` (hanya yang belum dibaca)

### b) Mark Read
`POST /api/notifikasi.php?action=mark_read`
```json
{ "id": 12 }
```
