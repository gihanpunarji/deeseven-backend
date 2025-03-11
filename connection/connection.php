<?php

class Database {
    public static $connection;

    public static function setupConnection() {
        if (!isset(Database::$connection)) {
            Database::$connection = new mysqli("193.203.184.9", "u331468302_root", "DeezevenDB1", "u331468302_deezeven");

            if (Database::$connection->connect_error) {
                die("Connection failed: " . Database::$connection->connect_error);
            }
        }
    }

    public static function search($query, $params = []) {
        Database::setupConnection();
        $stmt = Database::$connection->prepare($query);
        if ($params) {
            Database::bindParams($stmt, $params);
        }
        $stmt->execute();
        $resultset = $stmt->get_result();
        $stmt->close();
        return $resultset;
    }
    
    public static function iud($query, $params = []) {
        Database::setupConnection();
        $stmt = Database::$connection->prepare($query);
        if ($params) {
            Database::bindParams($stmt, $params);
        }
        $success = $stmt->execute();
        $stmt->close();
        return $success;
    }

    private static function bindParams($stmt, $params) {
        $types = ''; // Initialize the types string
        foreach ($params as $param) {
            if (is_int($param)) {
                $types .= 'i'; // Integer
            } elseif (is_double($param)) {
                $types .= 'd'; // Double
            } elseif (is_string($param)) {
                $types .= 's'; // String
            } else {
                $types .= 'b'; // Blob or other
            }
        }
        $stmt->bind_param($types, ...$params); // Bind parameters dynamically
    }
}

?>