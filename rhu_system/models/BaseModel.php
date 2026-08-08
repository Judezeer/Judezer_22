<?php
/**
 * Thin base model exposing a shared PDO connection.
 * Concrete models add their own table-specific methods.
 */
abstract class BaseModel
{
    protected PDO $db;
    public function __construct()
    {
        $this->db = Database::conn();
    }
}
