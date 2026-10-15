<?php
header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../app/config/Database.php';

$db = new Database();
$conn = $db->getConnection();

// Ambil action
$action = $_GET['action'] ?? $_POST['action'] ?? null;

if (!$action) {
    echo json_encode(["success" => false, "message" => "Parameter action tidak ditemukan"]);
    exit;
}

// ======================================================
// =============== 1. LIST MAHASISWA ====================
// ======================================================
if ($action === "list") {

    $query = "SELECT id, nama, nim, jurusan, alamat, golongan_darah, tempat_lahir, tanggal_lahir, no_hp, foto 
              FROM mahasiswa ORDER BY id ASC";

    $stmt = $conn->prepare($query);
    $stmt->execute();
    $result = $stmt->get_result();

    $mahasiswaList = [];

    while ($row = $result->fetch_assoc()) {
        $mahasiswaList[] = [
            "id" => $row['id'],
            "nama" => $row['nama'],
            "nim" => $row['nim'],
            "jurusan" => $row['jurusan'],
            "alamat" => $row['alamat'],
            "golongan_darah" => $row['golongan_darah'],
            "tempat_lahir" => $row['tempat_lahir'],
            "tanggal_lahir" => $row['tanggal_lahir'],
            "no_hp" => $row['no_hp'],
            "foto" => $row['foto']
        ];
    }

    echo json_encode(["success" => true, "data" => $mahasiswaList]);
    exit;
}

// ======================================================
// =============== 2. DETAIL MAHASISWA ==================
// ======================================================
if ($action === "detail") {

    $id = $_GET['id'] ?? null;
    if (!$id) {
        echo json_encode(["status" => "error", "message" => "ID tidak ada"]);
        exit;
    }

    $query = "SELECT id, nama, nim, jurusan, alamat, tempat_lahir, tanggal_lahir, golongan_darah, no_hp, foto
              FROM mahasiswa WHERE id = ? LIMIT 1";

    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $id);
    $stmt->execute();

    $result = $stmt->get_result();
    $data = $result->fetch_assoc();

    if ($data) {
        echo json_encode([
            "status" => "success",
            "id" => $data["id"],
            "nama" => $data["nama"],
            "nim" => $data["nim"],
            "jurusan" => $data["jurusan"],
            "alamat" => $data["alamat"],
            "tempat_lahir" => $data["tempat_lahir"],
            "tanggal_lahir" => $data["tanggal_lahir"],
            "golongan_darah" => $data["golongan_darah"],
            "no_hp" => $data["no_hp"],
            "foto" => $data["foto"]
        ]);
    } else {
        echo json_encode(["status" => "error", "message" => "Data tidak ditemukan"]);
    }

    exit;
}

// ======================================================
// =============== 3. TAMBAH MAHASISWA ==================
// ======================================================
if ($action === "add") {

    $nama = $_POST['nama'] ?? null;
    $nim = $_POST['nim'] ?? null;

    if (!$nama || !$nim) {
        echo json_encode(["success" => false, "message" => "Nama dan NIM wajib diisi"]);
        exit;
    }

    // Ambil semua input
    $jurusan         = $_POST['jurusan'] ?? null;
    $alamat          = $_POST['alamat'] ?? null;
    $golongan_darah  = $_POST['golongan_darah'] ?? null;
    $tempat_lahir    = $_POST['tempat_lahir'] ?? null;
    $tanggal_lahir   = $_POST['tanggal_lahir'] ?? null;
    $no_hp           = $_POST['no_hp'] ?? null;
    $foto_base64     = $_POST['foto'] ?? null;

    // Proses foto
    $foto = null;
    if ($foto_base64) {

        $uploadDir = __DIR__ . '/../public/uploads/mahasiswa/';
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);

        if (preg_match('/^data:image\/(\w+);base64,/', $foto_base64, $type)) {
            $foto_base64 = substr($foto_base64, strpos($foto_base64, ',') + 1);
            $ext = strtolower($type[1]);
            if (!in_array($ext, ['jpg', 'jpeg', 'png'])) $ext = 'jpg';
        } else {
            $ext = 'jpg';
        }

        $foto_data = base64_decode($foto_base64);

        $foto = uniqid() . "." . $ext;

        file_put_contents($uploadDir . $foto, $foto_data);
    }

    $query = "INSERT INTO mahasiswa 
              (nama, nim, jurusan, alamat, golongan_darah, tempat_lahir, tanggal_lahir, no_hp, foto)
              VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";

    $stmt = $conn->prepare($query);
    $stmt->bind_param(
        "sssssssss",
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

    if ($stmt->execute()) {
        echo json_encode(["success" => true, "message" => "Mahasiswa berhasil ditambahkan"]);
    } else {
        echo json_encode(["success" => false, "message" => "Gagal menambahkan mahasiswa"]);
    }

    exit;
}

// ======================================================
// =============== 4. HAPUS MAHASISWA ===================
// ======================================================
if ($action === "delete") {

    $id = $_POST['id'] ?? null;
    if (!$id) {
        echo json_encode(["status" => false, "message" => "Parameter ID tidak ditemukan"]);
        exit;
    }

    // Ambil foto lama
    $stmtFoto = $conn->prepare("SELECT foto FROM mahasiswa WHERE id = ?");
    $stmtFoto->bind_param("i", $id);
    $stmtFoto->execute();
    $fotoData = $stmtFoto->get_result()->fetch_assoc();
    $fotoFile = $fotoData['foto'] ?? null;

    // Hapus DB
    $stmt = $conn->prepare("DELETE FROM mahasiswa WHERE id = ?");
    $stmt->bind_param("i", $id);

    if ($stmt->execute()) {

        if ($fotoFile) {
            $path = __DIR__ . '/../public/uploads/mahasiswa/' . $fotoFile;
            if (file_exists($path)) unlink($path);
        }

        echo json_encode(["status" => true, "message" => "Data berhasil dihapus"]);
    } else {
        echo json_encode(["status" => false, "message" => "Gagal menghapus"]);
    }

    exit;
}



// ======================================================
// =============== 5. UBAH MAHASISWA ====================
// ======================================================
if ($action === "update") {

    $id = $_POST['id'] ?? null;
    $nama = $_POST['nama'] ?? null;
    $nim  = $_POST['nim'] ?? null;

    if (!$id || !$nama || !$nim) {
        echo json_encode(["success" => false, "message" => "ID, Nama, dan NIM wajib diisi"]);
        exit;
    }

    // Ambil input lain
    $jurusan = $_POST['jurusan'] ?? null;
    $alamat = $_POST['alamat'] ?? null;
    $golongan_darah = $_POST['golongan_darah'] ?? null;
    $tempat_lahir = $_POST['tempat_lahir'] ?? null;
    $tanggal_lahir = $_POST['tanggal_lahir'] ?? null;
    $no_hp = $_POST['no_hp'] ?? null;

    // Foto baru?
    $foto_base64 = $_POST['foto'] ?? null;
    $fotoBaru = null;

    if ($foto_base64) {

        $uploadDir = __DIR__ . '/../public/uploads/mahasiswa/';
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);

        if (preg_match('/^data:image\/(\w+);base64,/', $foto_base64, $type)) {
            $foto_base64 = substr($foto_base64, strpos($foto_base64, ',') + 1);
            $ext = strtolower($type[1]);
        } else {
            $ext = "jpg";
        }

        $foto_data = base64_decode($foto_base64);
        $fotoBaru = uniqid() . "." . $ext;

        file_put_contents($uploadDir . $fotoBaru, $foto_data);

        // Hapus foto lama
        $stmtFoto = $conn->prepare("SELECT foto FROM mahasiswa WHERE id = ?");
        $stmtFoto->bind_param("i", $id);
        $stmtFoto->execute();
        $fotoOld = $stmtFoto->get_result()->fetch_assoc()['foto'] ?? null;

        if ($fotoOld) {
            $oldFile = $uploadDir . $fotoOld;
            if (file_exists($oldFile)) unlink($oldFile);
        }
    }

    // Query update
    $query = "UPDATE mahasiswa SET 
                nama=?, nim=?, jurusan=?, alamat=?, golongan_darah=?, 
                tempat_lahir=?, tanggal_lahir=?, no_hp=?";

    if ($fotoBaru) $query .= ", foto='$fotoBaru'";

    $query .= " WHERE id=?";

    $stmt = $conn->prepare($query);
    $stmt->bind_param(
        "ssssssssi",
        $nama,
        $nim,
        $jurusan,
        $alamat,
        $golongan_darah,
        $tempat_lahir,
        $tanggal_lahir,
        $no_hp,
        $id
    );

    if ($stmt->execute()) {
        echo json_encode(["success" => true, "message" => "Data berhasil diperbarui"]);
    } else {
        echo json_encode(["success" => false, "message" => "Gagal update"]);
    }

    exit;
}


// ======================================================
// Default jika action tidak ditemukan
// ======================================================
echo json_encode(["success" => false, "message" => "Action tidak valid"]);
exit;
