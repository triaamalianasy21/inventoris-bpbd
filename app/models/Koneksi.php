<?php

class Koneksi
{
    public static function connect()
    {
        $host = "bgvb8uq9xlz3nxuaclxj-mysql.services.clever-cloud.com";
        $user = "uopyv96srm0jjq6l";
        $pass = "acS6YaXkL4c13EC1ljZl";
        $db   = "bgvb8uq9xlz3nxuaclxj"; // <-- DB BARU KAMU

        $conn = new mysqli($host, $user, $pass, $db);

        if ($conn->connect_error) {
            die("Koneksi database gagal: " . $conn->connect_error);
        }

        $conn->set_charset("utf8mb4");
        return $conn;
    }
}
