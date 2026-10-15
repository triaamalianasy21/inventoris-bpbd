<?php
class KegiatanModel
{
    private $conn;

    public function __construct($conn)
    {
        $this->conn = $conn;
    }

    public function getAll()
    {
        $result = $this->conn->query("SELECT * FROM kegiatan ORDER BY id DESC");
        return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    }

    public function getById($id)
    {
        $stmt = $this->conn->prepare("SELECT * FROM kegiatan WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }

    public function insert($user_id, $nama, $tanggal, $lokasi, $deskripsi, $foto)
    {
        $stmt = $this->conn->prepare("
            INSERT INTO kegiatan (user_id, nama, tanggal, lokasi, deskripsi, foto)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        $stmt->bind_param("isssss", $user_id, $nama, $tanggal, $lokasi, $deskripsi, $foto);
        return $stmt->execute();
    }

    public function update($id, $nama, $tanggal, $lokasi, $deskripsi, $foto)
    {
        $stmt = $this->conn->prepare("
            UPDATE kegiatan SET nama=?, tanggal=?, lokasi=?, deskripsi=?, foto=? WHERE id=?
        ");
        $stmt->bind_param("sssssi", $nama, $tanggal, $lokasi, $deskripsi, $foto, $id);
        return $stmt->execute();
    }

    public function delete($id)
    {
        $stmt = $this->conn->prepare("DELETE FROM kegiatan WHERE id = ?");
        $stmt->bind_param("i", $id);
        return $stmt->execute();
    }
}
