<?php

class PDOSingleton
{
    private const DSN = 'mysql:host=localhost;dbname=tree';
    private const USERNAME = 'root';
    private const PASSWORD = '';
    private const DRIVER_OPTION = [
        PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8'
    ];

    static private $PDOInstance;

    private function __clone() {}
    private function __construct() {}

    public static function getInstance()
    {
        if (!self::$PDOInstance) {
            try {
                self::$PDOInstance = new PDO(self::DSN, self::USERNAME, self::PASSWORD, self::DRIVER_OPTION);
                self::$PDOInstance->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            } catch (PDOException $e) {
                die("Error: PDO CONNECTION ERROR: " . $e->getMessage());
            }
        }

        return self::$PDOInstance;
    }
}