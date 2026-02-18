<?php
/**
 * V-Commerce - Veritabanı Bağlantısı (PDO Singleton)
 */

class Database
{
    private static $instance = null;
    private $pdo;

    private $host = 'localhost';
    private $dbname = 'vcommerce';
    private $username = 'root';
    private $password = 'rexe2026';

    private function __construct()
    {
        $this->connect();
    }

    private function connect()
    {
        try {
            $this->pdo = new PDO(
                "mysql:host={$this->host};dbname={$this->dbname};charset=utf8mb4",
                $this->username,
                $this->password,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                    PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
                ]
            );
        } catch (PDOException $e) {
            die("Veritabanı bağlantı hatası: " . $e->getMessage());
        }
    }

    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function getConnection()
    {
        // Bağlantı kopmuşsa yeniden bağlan
        try {
            $this->pdo->query('SELECT 1');
        } catch (PDOException $e) {
            $this->connect();
        }
        return $this->pdo;
    }

    // Reconnect - uzun işlemler için
    public static function reconnect()
    {
        $db = self::getInstance();
        $db->connect();
    }

    // Kısayol: sorgu çalıştır (otomatik reconnect)
    public static function query($sql, $params = [])
    {
        $pdo = self::getInstance()->getConnection();
        try {
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt;
        } catch (PDOException $e) {
            // MySQL gone away ise yeniden bağlan ve tekrar dene
            if (strpos($e->getMessage(), 'server has gone away') !== false || $e->getCode() == 'HY000') {
                self::reconnect();
                $pdo = self::getInstance()->getConnection();
                $stmt = $pdo->prepare($sql);
                $stmt->execute($params);
                return $stmt;
            }
            throw $e;
        }
    }

    // Tek satır getir
    public static function fetch($sql, $params = [])
    {
        return self::query($sql, $params)->fetch();
    }

    // Tüm satırları getir
    public static function fetchAll($sql, $params = [])
    {
        return self::query($sql, $params)->fetchAll();
    }

    // Son eklenen ID
    public static function lastInsertId()
    {
        return self::getInstance()->getConnection()->lastInsertId();
    }
}
