<?php
require_once __DIR__ . '/Koneksi.php';

class DonasiModel
{
    private $conn;

    public function __construct()
    {
        $this->conn = Koneksi::connect();
    }

    // Ambil semua donasi
    public function getDonasi()
    {
        $sql = "SELECT * FROM donasi ORDER BY created_at DESC";
        $result = $this->conn->query($sql);
        $data = [];
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $data[] = $row;
            }
        }
        return $data;
    }

    // Hitung donasi pending
    public function countPending()
    {
        $sql = "SELECT COUNT(*) AS total FROM donasi WHERE status='pending'";
        $res = $this->conn->query($sql);
        return $res ? (int)($res->fetch_assoc()['total'] ?? 0) : 0;
    }

    // Cari donasi by id
    public function find($id)
    {
        $stmt = $this->conn->prepare("SELECT * FROM donasi WHERE id=?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }

    // Update donasi
    public function updateDonasi($id, $data)
    {
        $stmt = $this->conn->prepare("UPDATE donasi SET kategori=?, nominal=?, metode=?, nama=?, telepon=?, email=?, pesan=?, bukti=?, status=? WHERE id=?");
        $stmt->bind_param(
            "sdsssssssi",
            $data['kategori'],
            $data['nominal'],
            $data['metode'],
            $data['nama'],
            $data['telepon'],
            $data['email'],
            $data['pesan'],
            $data['bukti'],
            $data['status'],
            $id
        );
        return $stmt->execute();
    }

    // Hapus donasi
    public function delete($id)
    {
        $stmt = $this->conn->prepare("DELETE FROM donasi WHERE id=?");
        $stmt->bind_param("i", $id);
        return $stmt->execute();
    }

    // Simpan donasi baru
    public function simpanDonasi($data)
    {
        $stmt = $this->conn->prepare("INSERT INTO donasi (kategori, nominal, metode, nama, telepon, email, pesan, bukti, status, created_at) VALUES (?,?,?,?,?,?,?,?,?,NOW())");
        $status = $data['status'] ?? 'pending';
        $stmt->bind_param(
            "sdsssssss",
            $data['kategori'],
            $data['nominal'],
            $data['metode'],
            $data['nama'],
            $data['telepon'],
            $data['email'],
            $data['pesan'],
            $data['bukti'],
            $status
        );
        return $stmt->execute();
    }
}
