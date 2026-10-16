<?php

class Koneksi
{
    private static $conn = null;

    public static function connect()
    {
        // Pakai koneksi yang sudah ada jika tersedia
        if (self::$conn !== null && self::$conn->ping()) {
            return self::$conn;
        }

        $host = "bgvb8uq9xlz3nxuaclxj-mysql.services.clever-cloud.com";
        $user = "uopyv96srm0jjq6l";
        $pass = "acS6YaXkL4c13EC1ljZl";
        $db   = "bgvb8uq9xlz3nxuaclxj";

        self::$conn = new mysqli($host, $user, $pass, $db);

        if (self::$conn->connect_error) {
            die("Koneksi database gagal: " . self::$conn->connect_error);
        }

        self::$conn->set_charset("utf8mb4");
        return self::$conn;
    }
}
