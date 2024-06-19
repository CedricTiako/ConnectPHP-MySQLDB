<?php

class ConnectMySQLDB {
    private $pdo;

    public function __construct($host, $dbname, $username, $password) {
        try {
            $this->pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password, [
                PDO::ATTR_PERSISTENT => true,
                PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8'
            ]);
            $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (PDOException $e) {
            error_log("Connection error: " . $e->getMessage());
            throw new Exception("Database connection error");
        }
    }

    public function create($table, $data) {
        try {
            $keys = implode(',', array_keys($data));
            $values = ':' . implode(',:', array_keys($data));
            $sql = "INSERT IGNORE INTO $table ($keys) VALUES ($values)";
            
            $stmt = $this->pdo->prepare($sql);
            $this->bindValues($stmt, $data);

            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("Insert error: " . $e->getMessage());
            return false;
        }
    }

    public function read($table, $conditions = [], $orderBy = '') {
        try {
            $sql = "SELECT * FROM $table";
            if (!empty($conditions)) {
                $sql .= $this->buildWhereClause($conditions);
            }
            if (!empty($orderBy)) {
                $sql .= " ORDER BY $orderBy";
            }

            $stmt = $this->pdo->prepare($sql);
            $this->bindValues($stmt, $conditions);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Read error: " . $e->getMessage());
            return [];
        }
    }

    public function update($table, $data, $conditions) {
        try {
            $setClauses = [];
            foreach ($data as $field => $value) {
                $setClauses[] = "$field = :$field";
            }
            $sql = "UPDATE $table SET " . implode(', ', $setClauses);
            if (!empty($conditions)) {
                $sql .= $this->buildWhereClause($conditions, 'where_');
            }

            $stmt = $this->pdo->prepare($sql);
            $this->bindValues($stmt, $data);
            $this->bindValues($stmt, $conditions, 'where_');
            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("Update error: " . $e->getMessage());
            return false;
        }
    }

    public function delete($table, $conditions) {
        try {
            $sql = "DELETE FROM $table";
            if (!empty($conditions)) {
                $sql .= $this->buildWhereClause($conditions);
            }
            
            $stmt = $this->pdo->prepare($sql);
            $this->bindValues($stmt, $conditions);
            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("Delete error: " . $e->getMessage());
            return false;
        }
    }

    private function buildWhereClause($conditions, $prefix = '') {
        $clauses = [];
        foreach ($conditions as $field => $value) {
            if (is_array($value)) {
                $operator = $value[0];
                $clauses[] = "$field $operator :{$prefix}$field";
            } else {
                $clauses[] = "$field = :{$prefix}$field";
            }
        }
        return ' WHERE ' . implode(' AND ', $clauses);
    }

    private function bindValues($stmt, $data, $prefix = '') {
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $stmt->bindValue(":{$prefix}$key", $value[1]);
            } else {
                $stmt->bindValue(":{$prefix}$key", $value);
            }
        }
    }

    public function executeCustomQuery($query, $params = []) {
        try {
            $stmt = $this->pdo->prepare($query);
            $this->bindValues($stmt, $params);
            $stmt->execute();

            if (strpos(strtoupper($query), 'SELECT') === 0) {
                return $stmt->fetchAll(PDO::FETCH_ASSOC);
            }
            return $stmt->rowCount();
        } catch (PDOException $e) {
            error_log("Custom query error: " . $e->getMessage());
            return false;
        }
    }

    public function customSelect($tables, $conditions = [], $joins = [], $orderBy = '') {
        try {
            $tableList = implode(', ', $tables);
            $sql = "SELECT * FROM $tableList";
            foreach ($joins as $join) {
                $sql .= " $join";
            }
            if (!empty($conditions)) {
                $sql .= $this->buildWhereClause($conditions);
            }
            if (!empty($orderBy)) {
                $sql .= " ORDER BY $orderBy";
            }
            
            $stmt = $this->pdo->prepare($sql);
            $this->bindValues($stmt, $conditions);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Custom select error: " . $e->getMessage());
            return [];
        }
    }

    public function executeStoredProcedure($procedureName, $params = []) {
        try {
            $placeholders = implode(', ', array_fill(0, count($params), '?'));
            $stmt = $this->pdo->prepare("CALL $procedureName($placeholders)");
            $stmt->execute(array_values($params));
        } catch (PDOException $e) {
            error_log("Stored procedure execution error: " . $e->getMessage());
            throw new Exception("Stored procedure execution error");
        }
    }

    public function executeStoredProcedureWithResults($procedureName, $params = []) {
        try {
            $placeholders = implode(', ', array_fill(0, count($params), '?'));
            $stmt = $this->pdo->prepare("CALL $procedureName($placeholders)");
            $stmt->execute(array_values($params));
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Stored procedure execution error: " . $e->getMessage());
            throw new Exception("Stored procedure execution error");
        }
    }

    public function lastInsertID() {
        try {
            return $this->pdo->lastInsertId();
        } catch (PDOException $e) {
            error_log("Last insert ID retrieval error: " . $e->getMessage());
            return false;
        }
    }
}
