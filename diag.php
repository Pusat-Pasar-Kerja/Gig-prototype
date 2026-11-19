<?php
require_once __DIR__ . '/includes/vacancy-store.php';
$db = gig_db();
echo "DB is " . ($db ? "connected" : "null") . "\n";
if ($db) {
    $stmt = $db->query("SELECT id FROM project_vacancies LIMIT 5");
    $rows = $stmt->fetchAll(PDO::FETCH_COLUMN);
    echo "Existing IDs in project_vacancies:\n";
    print_r($rows);
}
