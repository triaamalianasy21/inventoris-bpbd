<?php
// ===================================================
// 🎓 Mahasiswa Controller
// ===================================================

class MahasiswaController
{
    private $conn;

    public function __construct()
    {
        require_once __DIR__ . '/../models/Koneksi.php';
        $this->conn = Koneksi::connect(); // ✅ koneksi global
    }

    // ===================================================
    // INDEX (TAMPIL SEMUA MAHASISWA)
    // ===================================================
    public function index()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $role = $_SESSION['role'] ?? '';
        $user_id = $_SESSION['user_id'] ?? 0;

        if ($role === 'admin') {
            $query = "SELECT * FROM mahasiswa ORDER BY id DESC";
            $result = $this->conn->query($query);
        } else {
            $stmt = $this->conn->prepare("SELECT * FROM mahasiswa WHERE user_id = ?");
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $result = $stmt->get_result();
        }

        $data = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];

        include __DIR__ . '/../views/mahasiswa/index.php';
    }

    // ===================================================
    // TAMBAH MAHASISWA
    // ===================================================
    public function tambah()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $user_id        = $_SESSION['user_id'] ?? 0;
            $nama           = trim($_POST['nama']);
            $nim            = trim($_POST['nim']);
            $jurusan        = trim($_POST['jurusan']);
            $alamat         = trim($_POST['alamat']);
            $golongan_darah = trim($_POST['golongan_darah']);
            $tempat_lahir   = trim($_POST['tempat_lahir']);
            $tanggal_lahir  = trim($_POST['tanggal_lahir']);
            $no_hp          = trim($_POST['no_hp']);

            // =============================
            // Upload Foto Mahasiswa
            // =============================
            $foto = null;
            if (!empty($_FILES['foto']['name'])) {
                // path folder sesuai struktur androidweb/public/uploads/mahasiswa
                $folder = dirname(__DIR__, 2) . '/public/uploads/mahasiswa/';

                // pastikan folder ada
                if (!is_dir($folder)) {
                    mkdir($folder, 0777, true);
                }

                // ambil ekstensi file
                $ext = strtolower(pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION));

                // buat nama file unik
                $foto = 'mahasiswa_' . time() . '.' . $ext;

                $uploadPath = $folder . $foto;

                // pindahkan file
                if (!move_uploaded_file($_FILES['foto']['tmp_name'], $uploadPath)) {
                    $foto = null; // gagal upload
                }
            }

            // =============================
            //Simpan data mahasiswa
            // =============================
            $stmt = $this->conn->prepare("
            INSERT INTO mahasiswa 
            (user_id, nama, nim, jurusan, alamat, golongan_darah, tempat_lahir, tanggal_lahir, no_hp, foto)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
            $stmt->bind_param(
                "isssssssss",
                $user_id,
                $nama,
                $nim,
                $jurusan,
                $alamat,
                $golongan_darah,
                $tempat_lahir,
                $tanggal_lahir,
                $no_hp,
                $foto
            );
            $stmt->execute();

            header("Location: index.php?page=mahasiswa");
            exit;
        }

        include __DIR__ . '/../views/mahasiswa/tambah.php';
    }


    // ===================================================
    // UBAH MAHASISWA
    // ===================================================
    public function ubah()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $id = $_GET['id'] ?? null;
        if (!$id) {
            die("ID mahasiswa tidak ditemukan.");
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $nama           = trim($_POST['nama']);
            $nim            = trim($_POST['nim']);
            $jurusan        = trim($_POST['jurusan']);
            $alamat         = trim($_POST['alamat']);
            $golongan_darah = trim($_POST['golongan_darah']);
            $tempat_lahir   = trim($_POST['tempat_lahir']);
            $tanggal_lahir  = trim($_POST['tanggal_lahir']);
            $no_hp          = trim($_POST['no_hp']);
            $foto_lama      = $_POST['foto_lama'] ?? null;

            // Path upload 
            $folder = dirname(__DIR__, 2) . '/public/uploads/mahasiswa/';
            if (!is_dir($folder)) mkdir($folder, 0777, true);

            $foto = $foto_lama;
            if (!empty($_FILES['foto']['name'])) {
                $ext = strtolower(pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION));
                $fotoBaru = 'mahasiswa_' . time() . '.' . $ext;
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
                UPDATE mahasiswa SET 
                    nama=?, nim=?, jurusan=?, alamat=?, golongan_darah=?, 
                    tempat_lahir=?, tanggal_lahir=?, no_hp=?, foto=? 
                WHERE id=?
            ");
            $stmt->bind_param(
                "sssssssssi",
                $nama,
                $nim,
                $jurusan,
                $alamat,
                $golongan_darah,
                $tempat_lahir,
                $tanggal_lahir,
                $no_hp,
                $foto,
                $id
            );
            $stmt->execute();

            header("Location: index.php?page=mahasiswa");
            exit;
        }

        // Ambil data lama
        $stmt = $this->conn->prepare("SELECT * FROM mahasiswa WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $data = $stmt->get_result()->fetch_assoc();

        include __DIR__ . '/../views/mahasiswa/ubah.php';
    }

    // ===================================================
    // DETAIL MAHASISWA
    // ===================================================
    public function detail()
    {
        $id = $_GET['id'] ?? null;
        if (!$id) {
            die("ID mahasiswa tidak ditemukan.");
        }

        $stmt = $this->conn->prepare("SELECT * FROM mahasiswa WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $data = $stmt->get_result()->fetch_assoc();

        include __DIR__ . '/../views/mahasiswa/detail.php';
    }

    // ===================================================
    // HAPUS MAHASISWA
    // ===================================================
    public function hapus()
    {
        $id = $_GET['id'] ?? null;
        if (!$id) {
            die("ID mahasiswa tidak ditemukan.");
        }

        // Ambil foto dulu
        $stmt = $this->conn->prepare("SELECT foto FROM mahasiswa WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();

        $folder = dirname(__DIR__, 2) . '/public/uploads/mahasiswa/';
        if (!empty($result['foto']) && file_exists($folder . $result['foto'])) {
            unlink($folder . $result['foto']);
        }

        // Hapus data
        $stmt = $this->conn->prepare("DELETE FROM mahasiswa WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();

        header("Location: index.php?page=mahasiswa");
        exit;
    }
}
