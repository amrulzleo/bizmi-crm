<?php
/**
 * BizMi CRM Database Connection
 * 
 * Handles database connections and operations
 * Created by: Amrullah Khan
 * Email: amrulzlionheart@gmail.com
 * Date: November 11, 2025
 * Version: 1.0.0
 */

class Database
{
    private static $instance = null;
    private $connection;
    private $host;
    private $dbname;
    private $username;
    private $password;
    private $charset;
    
    private function __construct()
    {
        $this->loadConfig();
        $this->connect();
    }
    
    /**
     * Get database instance (Singleton pattern)
     */
    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Load database configuration
     */
    private function loadConfig()
    {
        if (defined('DB_HOST')) {
            $this->host = DB_HOST;
            $this->dbname = DB_NAME;
            $this->username = DB_USER;
            $this->password = DB_PASS;
            $this->charset = defined('DB_CHARSET') ? DB_CHARSET : 'utf8mb4';
        } else {
            throw new Exception('Database configuration not found');
        }
    }
    
    /**
     * Connect to database
     */
    private function connect()
    {
        try {
            $dsn = "mysql:host={$this->host};dbname={$this->dbname};charset={$this->charset}";
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES {$this->charset}"
            ];
            
            $this->connection = new PDO($dsn, $this->username, $this->password, $options);
            
        } catch (PDOException $e) {
            throw new Exception('Database connection failed: ' . $e->getMessage());
        }
    }
    
    /**
     * Get PDO connection
     */
    public function getConnection()
    {
        return $this->connection;
    }
    
    /**
     * Execute a query and return results
     */
    public function query($sql, $params = [])
    {
        try {
            $stmt = $this->connection->prepare($sql);
            $stmt->execute($params);
            return $stmt;
        } catch (PDOException $e) {
            throw new Exception('Database query failed: ' . $e->getMessage());
        }
    }
    
    /**
     * Fetch all rows
     */
    public function fetchAll($sql, $params = [])
    {
        $stmt = $this->query($sql, $params);
        return $stmt->fetchAll();
    }
    
    /**
     * Fetch single row
     */
    public function fetch($sql, $params = [])
    {
        $stmt = $this->query($sql, $params);
        return $stmt->fetch();
    }
    
    /**
     * Fetch single column value
     */
    public function fetchColumn($sql, $params = [])
    {
        $stmt = $this->query($sql, $params);
        return $stmt->fetchColumn();
    }
    
    /**
     * Insert data and return last insert ID
     */
    public function insert($table, $data)
    {
        $columns = array_keys($data);
        $placeholders = ':' . implode(', :', $columns);
        $columnList = implode(', ', $columns);
        
        $sql = "INSERT INTO {$table} ({$columnList}) VALUES ({$placeholders})";
        
        $this->query($sql, $data);
        return $this->connection->lastInsertId();
    }
    
    /**
     * Update data
     */
    public function update($table, $data, $where, $whereParams = [])
    {
        $setParts = [];
        foreach (array_keys($data) as $column) {
            $setParts[] = "{$column} = :{$column}";
        }
        $setClause = implode(', ', $setParts);
        
        $sql = "UPDATE {$table} SET {$setClause} WHERE {$where}";
        
        $params = array_merge($data, $whereParams);
        $stmt = $this->query($sql, $params);
        return $stmt->rowCount();
    }
    
    /**
     * Delete data
     */
    public function delete($table, $where, $params = [])
    {
        $sql = "DELETE FROM {$table} WHERE {$where}";
        $stmt = $this->query($sql, $params);
        return $stmt->rowCount();
    }
    
    /**
     * Begin transaction
     */
    public function beginTransaction()
    {
        return $this->connection->beginTransaction();
    }
    
    /**
     * Commit transaction
     */
    public function commit()
    {
        return $this->connection->commit();
    }
    
    /**
     * Rollback transaction
     */
    public function rollback()
    {
        return $this->connection->rollback();
    }
    
    /**
     * Check if table exists
     */
    public function tableExists($tableName)
    {
        $sql = "SHOW TABLES LIKE ?";
        $stmt = $this->query($sql, [$tableName]);
        return $stmt->rowCount() > 0;
    }
    
    /**
     * Get table columns
     */
    public function getTableColumns($tableName)
    {
        $sql = "SHOW COLUMNS FROM {$tableName}";
        return $this->fetchAll($sql);
    }
    
    /**
     * Execute SQL file
     */
    public function executeSqlFile($filePath)
    {
        if (!file_exists($filePath)) {
            throw new Exception("SQL file not found: {$filePath}");
        }
        
        $sql = file_get_contents($filePath);
        
        // Remove comments and split into statements
        $sql = preg_replace('/--.*\n/', '', $sql);
        $sql = preg_replace('/\/\*.*?\*\//s', '', $sql);
        
        $statements = array_filter(array_map('trim', explode(';', $sql)));
        
        $this->beginTransaction();
        
        try {
            foreach ($statements as $statement) {
                if (!empty($statement)) {
                    $this->connection->exec($statement);
                }
            }
            $this->commit();
        } catch (Exception $e) {
            $this->rollback();
            throw $e;
        }
    }
    
    /**
     * Get database schema version
     */
    public function getSchemaVersion()
    {
        try {
            $sql = "SELECT setting_value FROM settings WHERE setting_key = 'schema_version'";
            return $this->fetchColumn($sql);
        } catch (Exception $e) {
            return null;
        }
    }
    
    /**
     * Set database schema version
     */
    public function setSchemaVersion($version)
    {
        $data = [
            'setting_key' => 'schema_version',
            'setting_value' => $version,
            'setting_type' => 'string',
            'description' => 'Database schema version',
            'is_system' => 1
        ];
        
        // Try to update first
        $updated = $this->update(
            'settings', 
            ['setting_value' => $version], 
            'setting_key = ?', 
            ['schema_version']
        );
        
        // If no rows were updated, insert new record
        if ($updated === 0) {
            $this->insert('settings', $data);
        }
    }
    
    /**
     * Test database connection
     */
    public static function testConnection($host, $dbname, $username, $password)
    {
        try {
            $dsn = "mysql:host={$host};dbname={$dbname};charset=utf8mb4";
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false
            ];
            
            $connection = new PDO($dsn, $username, $password, $options);
            $connection = null; // Close connection
            return true;
            
        } catch (PDOException $e) {
            return false;
        }
    }
    
    /**
     * Prevent cloning
     */
    private function __clone() {}
    
    /**
     * Prevent unserialization
     */
    public function __wakeup()
    {
        throw new Exception("Cannot unserialize singleton");
    }
}
?>