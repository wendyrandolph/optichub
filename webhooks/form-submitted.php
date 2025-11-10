<?php
// Database connection
$host = "localhost";
$username = "ubrisvxfyeawz";
$password = 'j6t$DJgEg9sR';
$database = "dbbxdc25dwgnog";
$charset = "utf8mb4";

$dsn = "mysql:host=$host;dbname=$database;charset=$charset";

try {
  $pdo = new PDO($dsn, $username, $password);
  $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
  http_response_code(500);
  echo 'Database error: ' . $e->getMessage();
  exit;
}

// Get query params from webhook URL
$clientId = $_GET['client_id'] ?? null;
$taskId = $_GET['task_id'] ?? null;

// Raw form submission (body)
$rawData = json_encode($_REQUEST); // OR file_get_contents('php://input')

if ($clientId && $taskId && $rawData) {
  $stmt = $pdo->prepare("
    INSERT INTO form_submissions (client_id, task_id, form_url, data) 
    VALUES (:client_id, :task_id, :form_url, :data)
  ");
  $stmt->execute([
    ':client_id' => $clientId,
    ':task_id' => $taskId,
    ':form_url' => $_SERVER['REQUEST_URI'],
    ':data' => $rawData
  ]);

  // ✅ Auto-complete the task
  $update = $pdo->prepare("UPDATE tasks SET status = 'completed' WHERE id = :id");
  $update->execute([':id' => $taskId]);
  
  echo '✅ Logged successfully';
} else {
  http_response_code(400);
  echo '❌ Missing required data';
}
