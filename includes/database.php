<?php


class Database {
    private $pdo;
    private $dbPath;
    
    public function __construct($dbPath = null) {
        if ($dbPath === null) {
            $this->dbPath = defined('DB_PATH') ? DB_PATH : __DIR__ . '/../db/database.sqlite';
        } else {
            $this->dbPath = $dbPath;
        }
        $this->connect();
    }
    
    private function connect() {
        try {
            $this->pdo = new PDO('sqlite:' . $this->dbPath);
            $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            $this->pdo->setAttribute(PDO::ATTR_TIMEOUT, 30);
            $this->pdo->exec('PRAGMA journal_mode=WAL');
            $this->pdo->exec('PRAGMA synchronous=NORMAL');
            $this->pdo->exec('PRAGMA busy_timeout=30000');
        } catch (PDOException $e) {
            die('Database connection failed: ' . $e->getMessage());
        }
    }
    
    public function getConnection() {
        return $this->pdo;
    }
    
    public function query($sql, $params = []) {
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt;
        } catch (PDOException $e) {
            error_log('Database query error: ' . $e->getMessage());
            throw $e;
        }
    }
    
    public function fetchAll($sql, $params = []) {
        $stmt = $this->query($sql, $params);
        return $stmt->fetchAll();
    }
    
    public function fetchOne($sql, $params = []) {
        $stmt = $this->query($sql, $params);
        return $stmt->fetch();
    }
    
    public function insert($table, $data) {
        $columns = implode(',', array_keys($data));
        $placeholders = ':' . implode(', :', array_keys($data));
        $sql = "INSERT INTO {$table} ({$columns}) VALUES ({$placeholders})";
        
        $this->query($sql, $data);
        return $this->pdo->lastInsertId();
    }
    
    public function update($table, $data, $where, $whereParams = []) {
        $setClause = [];
        $params = [];
        
        foreach ($data as $key => $value) {
            $paramName = "set_{$key}";
            $setClause[] = "{$key} = :{$paramName}";
            $params[$paramName] = $value;
        }
        $setClause = implode(', ', $setClause);
        
        $whereClause = $where;
        $whereIndex = 0;
        foreach ($whereParams as $value) {
            $paramName = "where_{$whereIndex}";
            $whereClause = preg_replace('/\?/', ":{$paramName}", $whereClause, 1);
            $params[$paramName] = $value;
            $whereIndex++;
        }
        
        $sql = "UPDATE {$table} SET {$setClause} WHERE {$whereClause}";
        
        try {
            $stmt = $this->pdo->prepare($sql);
            $result = $stmt->execute($params);
            return $stmt->rowCount();
        } catch (Exception $e) {
            error_log("Update error: " . $e->getMessage());
            return false;
        }
    }
    
    public function delete($table, $where, $params = []) {
        $sql = "DELETE FROM {$table} WHERE {$where}";
        $stmt = $this->query($sql, $params);
        return $stmt->rowCount();
    }
    
    public function beginTransaction() {
        return $this->pdo->beginTransaction();
    }
    
    public function commit() {
        return $this->pdo->commit();
    }
    
    public function rollback() {
        return $this->pdo->rollback();
    }
}
?>
