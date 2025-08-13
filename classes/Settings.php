<?php
/**
 * Settings Management Class
 */

class Settings {
    private $db;

    public function __construct($database) {
        $this->db = $database;
    }

    public function getAllSettings() {
        $stmt = $this->db->prepare("SELECT `setting_key`, `setting_value` FROM settings");
        $stmt->execute();
        $rows = $stmt->fetchAll();
        $map = [];
        foreach ($rows as $row) {
            $map[$row['setting_key']] = $row['setting_value'];
        }
        return $map;
    }

    public function setSettings($keyValuePairs) {
        $this->db->getConnection()->beginTransaction();
        try {
            foreach ($keyValuePairs as $key => $value) {
                $stmt = $this->db->prepare("INSERT INTO settings(`setting_key`, `setting_value`) VALUES(?, ?) ON DUPLICATE KEY UPDATE `setting_value` = VALUES(`setting_value`)");
                $stmt->execute([$key, $value]);
            }
            $this->db->getConnection()->commit();
            return ['success' => true];
        } catch (Exception $e) {
            $this->db->getConnection()->rollback();
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
}


