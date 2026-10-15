<?php

class UsersModel
{
    private $conn;

    public function __construct($conn)
    {
        $this->conn = $conn;
    }

    public function getByUsername($username)
    {
        $stmt = $this->conn->prepare("
            SELECT id_user, nama, username, password, role, no_kontak, status
            FROM user
            WHERE username = ?
            LIMIT 1
        ");
        $stmt->bind_param("s", $username);
        $stmt->execute();

        $result = $stmt->get_result();
        return $result->fetch_assoc(); // array atau null
    }

    // Optional: kalau kamu mau nambah user via PHP (password bisa plaintext)
    public function createUser($nama, $username, $password, $role = 'pemohon', $no_kontak = null, $status = 'pending')
    {
        $stmt = $this->conn->prepare("
            INSERT INTO user (nama, username, password, role, no_kontak, status)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        $stmt->bind_param("ssssss", $nama, $username, $password, $role, $no_kontak, $status);

        return $stmt->execute();
    }
}
