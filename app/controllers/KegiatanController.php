<?php
// ===================================================
// 🎓 Kegiatan Controller
// ===================================================

class KegiatanController
{
    private $conn;

    public function __construct()
    {
        require_once __DIR__ . '/../models/Koneksi.php';
        $this->conn = Koneksi::connect(); // ✅ koneksi global
    }

    // ===================================================
    // INDEX (TAMPIL SEMUA KEGIATAN)
    // ===================================================
    public function index()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $role = $_SESSION['role'] ?? '';
        $user_id = $_SESSION['user_id'] ?? 0;

        if ($role === 'admin') {
            $query = "SELECT * FROM kegiatan ORDER BY id DESC";
            $result = $this->conn->query($query);
        } else {
            $stmt = $this->conn->prepare("SELECT * FROM kegiatan WHERE user_id = ?");
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $result = $stmt->get_result();
        }

        $data = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];

        include __DIR__ . '/../views/kegiatan/index.php';
    }

    // ===================================================
    // TAMBAH KEGIATAN
    // ===================================================
    public function tambah()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $user_id        = $_SESSION['user_id'] ?? 0;
            $nama           = trim($_POST['nama']);
            $tanggal            = trim($_POST['tanggal']);
            $lokasi        = trim($_POST['lokasi']);
            $deskripsi         = trim($_POST['deskripsi']);
            // =============================
            // Upload Foto Kegiatan
            // =============================
            $foto = null;
            if (!empty($_FILES['foto']['name'])) {
                // path folder sesuai struktur androidweb/public/uploads/kegiatan
                $folder = dirname(__DIR__, 2) . '/public/uploads/kegiatan/';

                // pastikan folder ada
                if (!is_dir($folder)) {
                    mkdir($folder, 0777, true);
                }

                // ambil ekstensi file
                $ext = strtolower(pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION));

                // buat nama file unik
                $foto = 'kegiatan_' . time() . '.' . $ext;

                $uploadPath = $folder . $foto;

                // pindahkan file
                if (!move_uploaded_file($_FILES['foto']['tmp_name'], $uploadPath)) {
                    $foto = null; // gagal upload
                }
            }

            // =============================
            // Simpan data kegiatan
            // =============================
            $stmt = $this->conn->prepare("
            INSERT INTO kegiatan 
            (user_id, nama, tanggal, lokasi, deskripsi, foto)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
            $stmt->bind_param(
                "isssss",
                $user_id,
                $nama,
                $tanggal,
                $lokasi,
                $deskripsi,
                $foto
            );
            $stmt->execute();

            header("Location: index.php?page=kegiatan");
            exit;
        }

        include __DIR__ . '/../views/kegiatan/tambah.php';
    }


    // ===================================================
    // UBAH KEGIATAN
    // ===================================================
    public function ubah()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $id = $_GET['id'] ?? null;
        if (!$id) {
            die("ID kegiatan tidak ditemukan.");
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $nama       = trim($_POST['nama']);
            $tanggal    = trim($_POST['tanggal']);
            $lokasi     = trim($_POST['lokasi']);
            $deskripsi  = trim($_POST['deskripsi']);
            $foto_lama  = $_POST['foto_lama'] ?? null;

            // Path upload 
            $folder = dirname(__DIR__, 2) . '/public/uploads/kegiatan/';
            if (!is_dir($folder)) mkdir($folder, 0777, true);

            $foto = $foto_lama;
            if (!empty($_FILES['foto']['name'])) {
                $ext = strtolower(pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION));
                $fotoBaru = 'kegiatan_' . time() . '.' . $ext;
                $uploadPath = $folder . $fotoBaru;

                if (move_uploaded_file($_FILES['foto']['tmp_name'], $uploadPath)) {
                    $foto = $fotoBaru;

                    // hapus foto lama jika ada
                    if (!empty($foto_lama) && file_exists($folder . $foto_lama)) {
                        unlink($folder . $foto_lama);
                    }
                }
            }

            $stmt = $this->conn->prepare("
                UPDATE kegiatan SET 
                    nama=?, tanggal=?, lokasi=?, deskripsi=?, foto=? 
                WHERE id=?
            ");
            $stmt->bind_param(
                "sssssi",
                $nama,
                $tanggal,
                $lokasi,
                $deskripsi,
                $foto,
                $id
            );
            $stmt->execute();

            header("Location: index.php?page=kegiatan");
            exit;
        }

        // Ambil data lama
        $stmt = $this->conn->prepare("SELECT * FROM kegiatan WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $data = $stmt->get_result()->fetch_assoc();

        include __DIR__ . '/../views/kegiatan/ubah.php';
    }

    // ===================================================
    // DETAIL KEGIATAN
    // ===================================================
    public function detail()
    {
        $id = $_GET['id'] ?? null;
        if (!$id) {
            die("ID kegiatan tidak ditemukan.");
        }

        $stmt = $this->conn->prepare("SELECT * FROM kegiatan WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $data = $stmt->get_result()->fetch_assoc();

        include __DIR__ . '/../views/kegiatan/detail.php';
    }

    // ===================================================
    // HAPUS KEGIATAN
    // ===================================================
    public function hapus()
    {
        $id = $_GET['id'] ?? null;
        if (!$id) {
            die("ID kegiatan tidak ditemukan.");
        }

        // Ambil foto dulu
        $stmt = $this->conn->prepare("SELECT foto FROM kegiatan WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();

        $folder = dirname(__DIR__, 2) . '/public/uploads/kegiatan/';
        if (!empty($result['foto']) && file_exists($folder . $result['foto'])) {
            unlink($folder . $result['foto']);
        }

        // Hapus data
        $stmt = $this->conn->prepare("DELETE FROM kegiatan WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();

        header("Location: index.php?page=kegiatan");
        exit;
    }
}
