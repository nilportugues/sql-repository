<?php

namespace NilPortugues\Tests;

use PDO;

class PDOProvider
{
    public static function create()
    {
        $pdo = new PDO('sqlite::memory:');
        $pdo->exec('
CREATE TABLE customers (
  customer_id INTEGER PRIMARY KEY AUTOINCREMENT,  
  customername CHAR(255),
  total_orders INT,
  total_earnings FLOAT,
  created_at DATETIME
);');
        return $pdo;
    }

    public static function destroy(PDO $pdo)
    {
        $pdo->exec('DROP TABLE customers;');
    }
}