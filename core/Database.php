<?php
/**
 * 数据库连接类
 */

class Database {
    private $mysqli;
    private $config;

    public function __construct($config) {
        $this->config = $config;
        $this->connect();
    }

    private function connect() {
        $this->mysqli = new mysqli(
            $this->config['db']['host'],
            $this->config['db']['user'],
            $this->config['db']['password'],
            $this->config['db']['database']
        );

        if ($this->mysqli->connect_error) {
            throw new Exception("数据库连接失败: " . $this->mysqli->connect_error);
        }

        $this->mysqli->set_charset($this->config['db']['charset']);
    }

    /**
     * 执行查询
     */
    public function query($sql, $types = '', $params = []) {
        $stmt = $this->mysqli->prepare($sql);
        
        if (!$stmt) {
            throw new Exception("SQL 准备失败: " . $this->mysqli->error);
        }
        
        if ($types && $params) {
            $stmt->bind_param($types, ...$params);
        }
        
        if (!$stmt->execute()) {
            throw new Exception("SQL 执行失败: " . $stmt->error);
        }
        
        return $stmt->get_result();
    }

    /**
     * 执行修改/删除操作
     */
    public function execute($sql, $types = '', $params = []) {
        $stmt = $this->mysqli->prepare($sql);
        
        if (!$stmt) {
            throw new Exception("SQL 准备失败: " . $this->mysqli->error);
        }
        
        if ($types && $params) {
            $stmt->bind_param($types, ...$params);
        }
        
        $result = $stmt->execute();
        
        return [
            'success' => $result,
            'insert_id' => $this->mysqli->insert_id,
            'affected_rows' => $this->mysqli->affected_rows,
            'error' => $stmt->error
        ];
    }

    /**
     * 关闭连接
     */
    public function close() {
        $this->mysqli->close();
    }

    /**
     * 获取错误信息
     */
    public function getError() {
        return $this->mysqli->error;
    }
}