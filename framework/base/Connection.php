<?php

namespace dee\base;

use Dee;

/**
 * Description of Connection
 *
 * @property string $errorInfo
 * @property string $rawSql
 * @property \PDO $pdo
 * 
 * @author Misbahul D Munir <misbahuldmunir@gmail.com>
 * @since 1.0
 */
class Connection
{
    public $dsn;
    public $username;
    public $password;
    public $attributes = [];
    public $pdoClass;
    public $debug = false;
    public $mode = \PDO::FETCH_ASSOC;
    public static $paramCount = 0;

    /**
     *
     * @var \PDO
     */
    private $_pdo;
    private $_errorInfo = [0];
    private $_rawSql;

    public function __get($name)
    {
        $method = 'get' . $name;
        if (method_exists($this, $method)) {
            return call_user_func([$this, $method]);
        }
    }

    public function open()
    {
        if ($this->_pdo === null) {
            $pdoClass = $this->pdoClass ?: 'PDO';
            $dsn = $this->dsn;
            if (strncmp('sqlite:@', $dsn, 8) === 0) {
                $dsn = 'sqlite:' . Dee::getAlias(substr($dsn, 7));
            }
            $defaults = [
                \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION
            ];
            $attributes = $this->attributes;
            foreach ($defaults as $key => $value) {
                if (!array_key_exists($key, $attributes)) {
                    $attributes[$key] = $value;
                }
            }
            $this->_pdo = new $pdoClass($dsn, $this->username, $this->password, $attributes);
        }
    }

    /**
     *
     * @return \PDO
     */
    public function getPdo()
    {
        $this->open();
        return $this->_pdo;
    }

    public function beginTransaction()
    {
        return $this->getPdo()->beginTransaction();
    }

    public function commit()
    {
        return $this->getPdo()->commit();
    }

    public function rollback()
    {
        return $this->getPdo()->rollBack();
    }

    protected function bindValues($statement, $params = [])
    {
        foreach ($params as $key => $value) {
            if (strncmp($key, ':', 1) !== 0) {
                $key = ':' . $key;
            }
            if (is_array($value) && isset($value[0], $value[1])) {
                $statement->bindValue($key, $value[0], $value[1]);
            } elseif ($value === null) {
                $statement->bindValue($key, $value, \PDO::PARAM_NULL);
            } elseif (is_int($value)) {
                $statement->bindValue($key, $value, \PDO::PARAM_INT);
            } else {
                $statement->bindValue($key, $value);
            }
        }
    }

    public function queryAll($sql, $params = [])
    {
        $this->_rawSql = $this->buildRawSql($sql, $params);
        $statement = $this->getPdo()->prepare($sql);
        $this->bindValues($statement, $params);
        $this->executeInternal($statement);
        $result = $statement->fetchAll($this->mode);
        $this->_errorInfo = $statement->errorInfo();
        $statement->closeCursor();
        return $result;
    }

    public function queryOne($sql, $params = [])
    {
        $this->_rawSql = $this->buildRawSql($sql, $params);
        $statement = $this->getPdo()->prepare($sql);
        $this->bindValues($statement, $params);
        $this->executeInternal($statement);
        $result = $statement->fetch($this->mode);
        $this->_errorInfo = $statement->errorInfo();
        $statement->closeCursor();
        return $result;
    }

    public function queryScalar($sql, $params = [])
    {
        $this->_rawSql = $this->buildRawSql($sql, $params);
        $statement = $this->getPdo()->prepare($sql);
        $this->bindValues($statement, $params);
        $this->executeInternal($statement);
        $result = $statement->fetchColumn();
        $this->_errorInfo = $statement->errorInfo();
        $statement->closeCursor();
        return $result;
    }

    public function queryColumn($sql, $params = [])
    {
        $this->_rawSql = $this->buildRawSql($sql, $params);
        $statement = $this->getPdo()->prepare($sql);
        $this->bindValues($statement, $params);
        $this->executeInternal($statement);
        $result = $statement->fetchAll(\PDO::FETCH_COLUMN, 0);
        $this->_errorInfo = $statement->errorInfo();
        $statement->closeCursor();
        return $result;
    }

    public function execute($sql, $params = [])
    {
        $this->_rawSql = $this->buildRawSql($sql, $params);
        $statement = $this->getPdo()->prepare($sql);
        $this->bindValues($statement, $params);
        $this->executeInternal($statement);
        $result = $statement->rowCount();
        $this->_errorInfo = $statement->errorInfo();
        return $result;
    }

    protected function executeInternal($statement)
    {
        try{
            $statement->execute();
        } catch (\PDOException $px){
            $message = $px->getMessage() . "\nThe SQL being executed was:\n{$this->_rawSql}";
            throw new \Exception($message, 0, $px);
        } catch (\Exception $ex) {
            $message = "The SQL being executed was:\n{$this->_rawSql}";
            throw new \Exception($message, $ex->getCode(), $ex);
        } catch (\Throwable $th){
            $message = "The SQL being executed was:\n{$this->_rawSql}";
            throw new \Exception($message, $th->getCode(), $th);
        }
    }

    public function insert($table, array $columns, &$params = [])
    {
        if (empty($columns)) {
            return 0;
        }
        $names = [];
        $values = [];
        foreach ($columns as $key => $value) {
            $names[] = $key;
            if (is_string($value) && strncmp($value, 'dbexp:', 6) === 0) {
                $values[] = substr($value, 6);
            } elseif ($value === null) {
                $values[] = 'NULL';
            } else{
                $paramName = ':p' . count($params);
                $params[$paramName] = $value;
                $values[] = $paramName;
            }
        }
        $sql = "INSERT INTO {$table}(" . implode(', ', $names) . ') VALUES(' . implode(', ', $values) . ')';
        return $this->execute($sql, $params);
    }

    public function update($table, array $columns, $condition = '', &$params = [], $extra = '')
    {
        if (empty($columns)) {
            return 0;
        }
        $names = [];
        foreach ($columns as $key => $value) {
            if (is_string($value) && strncmp($value, 'dbexp:', 6) === 0) {
                $names[] = "$key = " . substr($value, 6);
            } elseif ($value === null) {
                $names[] = "$key = NULL";
            } else{
                $paramName = ':p' . count($params);
                $params[$paramName] = $value;
                $names[] = "$key = $paramName";
            }
        }
        $sql = "UPDATE $table SET " . implode(', ', $names);
        $condition = $this->buildCondition($condition, $params);
        if (!empty($condition)) {
            $sql .= " WHERE $condition";
        }
        if ($extra) {
            $sql .= ' ' . $extra;
        }
        return $this->execute($sql, $params);
    }

    public function delete($table, $condition = '', &$params = [], $extra = '')
    {
        $sql = "DELETE FROM $table";
        $condition = $this->buildCondition($condition, $params);
        if (!empty($condition)) {
            $sql .= " WHERE $condition";
        }
        if ($extra) {
            $sql .= ' ' . $extra;
        }
        return $this->execute($sql, $params);
    }

    public function buildCondition($condition, &$params)
    {
        if (is_array($condition)) {
            $result = [];
            foreach ($condition as $key => $value) {
                if (is_int($key)) {
                    $result[] = $value;
                } elseif ($value === null) { // build is null
                    $result[] = "$key IS NULL";
                } elseif (is_array($value)) { // build in condition
                    $n = count($value);
                    if ($n > 1) {
                        $ins = [];
                        foreach ($value as $val) {
                            $paramName = ':p' . count($params);
                            $ins[] = $paramName;
                            $params[$paramName] = $val;
                        }
                        $result[] = "$key IN(" . implode(', ', $ins) . ')';
                    } elseif ($n === 1) { // =
                        $paramName = ':p' . count($params);
                        $result[] = "$key = $paramName";
                        $params[$paramName] = reset($value);
                    } else {
                        $result[] = '1=0';
                    }
                } elseif (strncmp($value, 'dbexp:', 6) === 0) {
                    $result[] = "$key = " . substr($value, 6);
                } else { // biasa, field = value
                    $paramName = ':p' . count($params);
                    $result[] = "$key = $paramName";
                    $params[$paramName] = $value;
                }
            }
            return count($result) ? '(' . implode(') AND (', $result) .')' : '';
        }
        return $condition;
    }

    public function buildRawSql($sql, $params = [])
    {
        $replace = [];
        foreach ($params as $key => $value) {
            if (is_array($value) && isset($value[0], $value[1])) {
                switch ($value[1]) {
                    case \PDO::PARAM_INT:
                        $replace[$key] = $value[0];
                        break;
                    case \PDO::PARAM_NULL:
                        $replace[$key] = 'NULL';
                        break;
                    default:
                        $replace[$key] = "'" . addslashes($value[0]) . "'";
                        break;
                }
            } elseif ($value === null) {
                $replace[$key] = 'NULL';
            } elseif (is_int($value)) {
                $replace[$key] = $value;
            } else {
                $replace[$key] = "'" . addslashes($value) . "'";
            }
        }
        return strtr($sql, $replace);
    }

    public function getErrorInfo()
    {
        if ($this->_errorInfo[0] != 0 && isset($this->_errorInfo[2])) {
            return $this->_errorInfo[2];
        }
    }

    public function getRawSql()
    {
        return $this->_rawSql;
    }

    public function lastInsertId($name = null)
    {
        return $this->getPdo()->lastInsertId($name);
    }
}
