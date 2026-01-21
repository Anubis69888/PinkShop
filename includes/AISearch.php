<?php
require_once 'db.php';

class AISearch {
    private $db;

    public function __construct() {
        $this->db = new DB();
    }

    public function search($query) {
        $products = $this->db->read('products');
        $results = [];
        $query = mb_strtolower(trim($query));

        if (empty($query)) return [];

        foreach ($products as $p) {
            $score = 0;
            $name = mb_strtolower($p['name']);
            $desc = mb_strtolower($p['description'] ?? '');

            // 1. Exact Match Logic
            if ($name === $query) $score += 100;

            // 2. Partial Match Logic
            if (mb_strpos($name, $query) !== false) $score += 50;
            if (mb_strpos($desc, $query) !== false) $score += 20;

            // 3. Typo Tolerance / Fuzzy Logic (Simulated AI)
            // Calculate similarity percentage
            similar_text($query, $name, $percent);
            if ($percent > 60) $score += ($percent / 2); // Add up to 50 points

            // 4. Keyword Association (Mock Knowledge Graph)
            // e.g., searching for "bear" matches "teddy"
            $keywords = [
                'ตุ๊กตา' => ['bear', 'doll', 'toy', 'plush'],
                'หมี' => ['bear', 'teddy'],
                'ของขวัญ' => ['gift', 'present'],
                'hello kitty' => ['kitty', 'cat', 'sanrio']
            ];

            foreach ($keywords as $key => $vals) {
                if (mb_strpos($query, $key) !== false || in_array($query, $vals)) {
                    // If query matches a keyword concept, boost items that contain related terms
                    foreach ($vals as $val) {
                         if (mb_strpos($name, $val) !== false) $score += 30;
                    }
                    if (mb_strpos($name, $key) !== false) $score += 30;
                }
            }

            if ($score >= 10) {
                $p['score'] = $score;
                $results[] = $p;
            }
        }

        // Sort by score descending
        usort($results, function($a, $b) {
            return $b['score'] <=> $a['score'];
        });

        return array_slice($results, 0, 5); // Return top 5
    }
}
?>
