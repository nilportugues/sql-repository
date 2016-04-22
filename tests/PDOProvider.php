<?php

namespace NilPortugues\Tests\Foundation;

use PDO;

class PDOProvider
{
    public static function create()
    {
        $pdo = new PDO('sqlite::memory:');
        $pdo->exec('
CREATE TABLE customers (
  customer_id INTEGER PRIMARY KEY AUTOINCREMENT,  
  customer_name CHAR(255),
  total_orders INT,
  total_earnings FLOAT,
  created_at DATETIME
);

INSERT INTO customers(customer_name, created_at, total_orders, total_earnings) VALUES("John Doe", "2014-12-11", 3, 25.125);
INSERT INTO customers(customer_name, created_at, total_orders, total_earnings) VALUES("Junichi Masuda", "2013-02-22", 3, 50978.125);
INSERT INTO customers(customer_name, created_at, total_orders, total_earnings) VALUES("Shigeru Miyamoto", "2010-12-01", 5, 47889850.125);
INSERT INTO customers(customer_name, created_at, total_orders, total_earnings) VALUES("Ken Sugimori", "2010-12-10", 4, 69158.687);
');

        return $pdo;
    }

    public static function destroy(PDO $pdo)
    {
        $pdo->exec('DROP TABLE customers;');
    }
}
