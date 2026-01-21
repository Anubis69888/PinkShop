<?php
class DB {
    private $dataDir;

    public function __construct() {
        $this->dataDir = __DIR__ . '/../data/';
        if (!file_exists($this->dataDir)) {
            mkdir($this->dataDir, 0777, true);
        }
    }

    private function getFilePath($table) {
        return $this->dataDir . $table . '.json';
    }

    public function read($table) {
        $file = $this->getFilePath($table);
        if (!file_exists($file)) {
            return [];
        }
        $json = file_get_contents($file);
        return json_decode($json, true) ?? [];
    }

    public function write($table, $data) {
        $file = $this->getFilePath($table);
        return file_put_contents($file, json_encode($data, JSON_PRETTY_PRINT), LOCK_EX);
    }

    public function find($table, $key, $value) {
        $data = $this->read($table);
        foreach ($data as $item) {
            if (isset($item[$key]) && $item[$key] == $value) {
                return $item;
            }
        }
        return null;
    }

    public function insert($table, $item) {
        $data = $this->read($table);
        // Auto-increment ID
        $lastId = 0;
        if (!empty($data)) {
            $lastItem = end($data);
            $lastId = $lastItem['id'] ?? 0;
        }
        $item['id'] = $lastId + 1;
        $item['created_at'] = date('Y-m-d H:i:s');
        
        $data[] = $item;
        $this->write($table, $data);
        return $item['id'];
    }

    public function update($table, $id, $updates) {
        $data = $this->read($table);
        foreach ($data as &$item) {
            if ($item['id'] == $id) {
                foreach ($updates as $k => $v) {
                    $item[$k] = $v;
                }
                $this->write($table, $data);
                return true;
            }
        }
        return false;
    }

    public function delete($table, $id) {
        $data = $this->read($table);
        $newData = [];
        $deleted = false;
        
        foreach ($data as $item) {
            if ($item['id'] != $id) {
                $newData[] = $item;
            } else {
                $deleted = true;
            }
        }
        
        if ($deleted) {
            $this->write($table, $newData);
            return true;
        }
        return false;
    }
}

