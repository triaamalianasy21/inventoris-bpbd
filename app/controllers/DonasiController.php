<?php
// ===================================================
// Donasi Controller
// ===================================================

require_once __DIR__ . '/../models/DonasiModel.php';

class DonasiController
{
    private $model;

    public function __construct()
    {
        $this->model = new DonasiModel();
    }

    // List donasi
    public function index()
    {
        if (session_status() === PHP_SESSION_NONE) session_start();

        $data = $this->model->getDonasi();
        include __DIR__ . '/../views/donasi/index.php';
    }

    // Tambah donasi (form + proses)
    public function tambah()
    {
        if (session_status() === PHP_SESSION_NONE) session_start();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $kategori = trim($_POST['kategori'] ?? '');
            $nominal  = (float)($_POST['nominal'] ?? 0);
            $metode   = trim($_POST['metode'] ?? '');
            $nama     = trim($_POST['nama'] ?? '');
            $telepon  = trim($_POST['telepon'] ?? '');
            $email    = trim($_POST['email'] ?? '');
            $pesan    = trim($_POST['pesan'] ?? '');
            $status   = 'pending';

            // Handle bukti upload
            $bukti = null;
            if (!empty($_FILES['bukti']['name'])) {
                $folder = dirname(__DIR__, 2) . '/public/uploads/donasi/';
                if (!is_dir($folder)) mkdir($folder, 0777, true);

                $ext = strtolower(pathinfo($_FILES['bukti']['name'], PATHINFO_EXTENSION));
                $fileName = 'donasi_' . time() . '.' . $ext;
                if (move_uploaded_file($_FILES['bukti']['tmp_name'], $folder . $fileName)) {
                    $bukti = $fileName;
                }
            }

            $data = [
                'kategori' => $kategori,
                'nominal'  => $nominal,
                'metode'   => $metode,
                'nama'     => $nama,
                'telepon'  => $telepon,
                'email'    => $email,
                'pesan'    => $pesan,
                'bukti'    => $bukti,
                'status'   => $status
            ];

            $this->model->simpanDonasi($data);

            header('Location: index.php?page=donasi');
            exit;
        }

        include __DIR__ . '/../views/donasi/tambah.php';
    }

    // Ubah donasi
    public function ubah()
    {
        if (session_status() === PHP_SESSION_NONE) session_start();

        $id = $_GET['id'] ?? null;
        if (!$id) die('ID donasi tidak ditemukan.');

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $kategori = trim($_POST['kategori'] ?? '');
            $nominal  = (float)($_POST['nominal'] ?? 0);
            $metode   = trim($_POST['metode'] ?? '');
            $nama     = trim($_POST['nama'] ?? '');
            $telepon  = trim($_POST['telepon'] ?? '');
            $email    = trim($_POST['email'] ?? '');
            $pesan    = trim($_POST['pesan'] ?? '');
            $status   = trim($_POST['status'] ?? 'pending');

            // handle bukti
            $bukti = $_POST['bukti_lama'] ?? null;
            if (!empty($_FILES['bukti']['name'])) {
                $folder = dirname(__DIR__, 2) . '/public/uploads/donasi/';
                if (!is_dir($folder)) mkdir($folder, 0777, true);

                $ext = strtolower(pathinfo($_FILES['bukti']['name'], PATHINFO_EXTENSION));
                $fileName = 'donasi_' . time() . '.' . $ext;
                if (move_uploaded_file($_FILES['bukti']['tmp_name'], $folder . $fileName)) {
                    // hapus lama
                    if (!empty($bukti) && file_exists($folder . $bukti)) unlink($folder . $bukti);
                    $bukti = $fileName;
                }
            }

            $data = [
                'kategori' => $kategori,
                'nominal'  => $nominal,
                'metode'   => $metode,
                'nama'     => $nama,
                'telepon'  => $telepon,
                'email'    => $email,
                'pesan'    => $pesan,
                'bukti'    => $bukti,
                'status'   => $status
            ];

            $this->model->updateDonasi($id, $data);
            header('Location: index.php?page=donasi');
            exit;
        }

        $data = $this->model->find($id);
        include __DIR__ . '/../views/donasi/ubah.php';
    }

    // Detail donasi
    public function detail()
    {
        $id = $_GET['id'] ?? null;
        if (!$id) die('ID donasi tidak ditemukan.');

        $data = $this->model->find($id);
        include __DIR__ . '/../views/donasi/detail.php';
    }

    // Hapus donasi
    public function hapus()
    {
        $id = $_GET['id'] ?? null;
        if (!$id) die('ID donasi tidak ditemukan.');

        $row = $this->model->find($id);
        $bukti = $row['bukti'] ?? null;
        $folder = dirname(__DIR__, 2) . '/public/uploads/donasi/';
        if (!empty($bukti) && file_exists($folder . $bukti)) unlink($folder . $bukti);

        $this->model->delete($id);
        header('Location: index.php?page=donasi');
        exit;
    }
}

