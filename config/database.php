<?php
/**
 * PDO Database connection (Singleton).
 * Uses prepared statements throughout the application.
 */

class Database
{
    private static ?PDO $instance = null;

    // XAMPP defaults; override via environment if needed
    private const DB_HOST = '127.0.0.1';
    private const DB_PORT = 3306;
    private const DB_NAME = 'rhu_makilala';
    private const DB_USER = 'root';
    private const DB_PASS = '';
    private const DB_CHAR = 'utf8mb4';

    /** Return a shared PDO instance. */
    public static function conn(): PDO
    {
        if (self::$instance instanceof PDO) {
            return self::$instance;
        }

        $dsn = sprintf(
            'mysql:host=%s;port=%d;dbname=%s;charset=%s',
            self::DB_HOST, self::DB_PORT, self::DB_NAME, self::DB_CHAR
        );

        try {
            self::$instance = new PDO($dsn, self::DB_USER, self::DB_PASS, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4, time_zone = '+08:00'",
            ]);
        } catch (PDOException $e) {
            http_response_code(500);
            die('<div style="font-family:sans-serif;padding:32px;">
                    <h2 style="color:#b91c1c;">Database connection failed</h2>
                    <p>Please make sure MySQL is running and the <code>rhu_makilala</code>
                    database has been imported.</p>
                    <p><small>' . htmlspecialchars($e->getMessage()) . '</small></p>
                 </div>');
        }
        return self::$instance;
    }
}
