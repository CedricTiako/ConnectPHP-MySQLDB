<?php

class ConnectMySQLDB {
    private $pdo;

    const DB_HOST = 'localhost';
    const DB_NAME = 'database_name';
    const DB_USER = 'username';
    const DB_PASS = 'password';

    public function __construct() {
        try {
            $this->pdo = new PDO("mysql:host=" . self::DB_HOST . ";dbname=" . self::DB_NAME, self::DB_USER, self::DB_PASS, [
                PDO::ATTR_PERSISTENT => true,
                PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8'
            ]);
            $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (PDOException $e) {
            $this->logError("Connection error", $e);
            throw new Exception("Database connection error");
        }
    }

    public function create(string $table, array $data): bool {
        $this->sanitizeData($data);
        try {
            $keys = implode(',', array_keys($data));
            $values = ':' . implode(',:', array_keys($data));
            $sql = "INSERT INTO $table ($keys) VALUES ($values)";

            $stmt = $this->pdo->prepare($sql);
            $this->bindValues($stmt, $data);

            return $stmt->execute();
        } catch (PDOException $e) {
            $this->logError("Insert error", $e);
            return false;
        }
    }

    public function read(string $table, array $conditions = [], string $orderBy = ''): array {
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
            $this->logError("Read error", $e);
            return [];
        }
    }

    public function update(string $table, array $data, array $conditions): bool {
        $this->sanitizeData($data);
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
            $this->logError("Update error", $e);
            return false;
        }
    }

    public function delete(string $table, array $conditions): bool {
        try {
            $sql = "DELETE FROM $table";
            if (!empty($conditions)) {
                $sql .= $this->buildWhereClause($conditions);
            }

            $stmt = $this->pdo->prepare($sql);
            $this->bindValues($stmt, $conditions);
            return $stmt->execute();
        } catch (PDOException $e) {
            $this->logError("Delete error", $e);
            return false;
        }
    }

    private function buildWhereClause(array $conditions, string $prefix = ''): string {
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

    private function bindValues($stmt, array $data, string $prefix = ''): void {
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $stmt->bindValue(":{$prefix}$key", $value[1]);
            } else {
                $stmt->bindValue(":{$prefix}$key", $value);
            }
        }
    }

    private function sanitizeData(array &$data): void {
        foreach ($data as $key => &$value) {
            $value = htmlspecialchars(strip_tags($value));
        }
    }

    private function logError(string $message, PDOException $exception): void {
        error_log("$message: " . $exception->getMessage());
    }

    public function executeCustomQuery(string $query, array $params = []) {
        try {
            $stmt = $this->pdo->prepare($query);
            $this->bindValues($stmt, $params);
            $stmt->execute();

            if (stripos($query, 'SELECT') === 0) {
                return $stmt->fetchAll(PDO::FETCH_ASSOC);
            }
            return $stmt->rowCount();
        } catch (PDOException $e) {
            $this->logError("Custom query error", $e);
            return false;
        }
    }

    public function customSelect(array $tables, array $conditions = [], array $joins = [], string $orderBy = ''): array {
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
            $this->logError("Custom select error", $e);
            return [];
        }
    }

    public function executeStoredProcedure(string $procedureName, array $params = []): void {
        try {
            $placeholders = implode(', ', array_fill(0, count($params), '?'));
            $stmt = $this->pdo->prepare("CALL $procedureName($placeholders)");
            $stmt->execute(array_values($params));
        } catch (PDOException $e) {
            $this->logError("Stored procedure execution error", $e);
            throw new Exception("Stored procedure execution error");
        }
    }

    public function executeStoredProcedureWithResults(string $procedureName, array $params = []): array {
        try {
            $placeholders = implode(', ', array_fill(0, count($params), '?'));
            $stmt = $this->pdo->prepare("CALL $procedureName($placeholders)");
            $stmt->execute(array_values($params));
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            $this->logError("Stored procedure execution error", $e);
            throw new Exception("Stored procedure execution error");
        }
    }

    public function lastInsertID(): mixed {
        try {
            return $this->pdo->lastInsertId();
        } catch (PDOException $e) {
            $this->logError("Last insert ID retrieval error", $e);
            return false;
        }
    }

    public function transaction(callable $callback): mixed {
        try {
            $this->pdo->beginTransaction();
            $result = $callback($this->pdo);
            $this->pdo->commit();
            return $result;
        } catch (Exception $e) {
            $this->pdo->rollBack();
            $this->logError("Transaction error", $e);
            throw $e;
        }
    }
}
