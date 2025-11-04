<?php
header('Content-Type: application/json');

$input = file_get_contents('php://input');
$data = json_decode($input ?: '{}', true);
$action = $_GET['action'] ?? ($data['action'] ?? '');
$roomId = preg_replace('/[^a-zA-Z0-9_-]/', '', ($_GET['roomId'] ?? ($data['roomId'] ?? '')));

$dataDir = __DIR__ . '/../data';
if (!is_dir($dataDir)) {
    @mkdir($dataDir, 0777, true);
}

function chatFilePath(string $roomId): string {
    return __DIR__ . '/../data/chat_' . $roomId . '.json';
}

if ($roomId === '') {
    echo json_encode(['ok' => false, 'error' => 'roomId requis']);
    exit;
}

$path = chatFilePath($roomId);
if (!file_exists($path)) {
    file_put_contents($path, json_encode(['roomId' => $roomId, 'messages' => []], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
}

if ($_SERVER['REQUEST_METHOD'] === 'GET' || $action === 'get') {
    $content = json_decode(file_get_contents($path) ?: '{}', true);
    echo json_encode(['ok' => true, 'chat' => $content]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim((string)($data['name'] ?? 'Anonyme'));
    $text = trim((string)($data['text'] ?? ''));
    if ($text === '') {
        echo json_encode(['ok' => false, 'error' => 'message vide']);
        exit;
    }
    $fp = fopen($path, 'c+');
    if ($fp === false) {
        echo json_encode(['ok' => false, 'error' => 'cannot open chat file']);
        exit;
    }
    flock($fp, LOCK_EX);
    $currentRaw = stream_get_contents($fp);
    $current = json_decode($currentRaw ?: '{}', true);
    if (!is_array($current)) $current = ['roomId' => $roomId, 'messages' => []];
    $messages = $current['messages'] ?? [];
    $messages[] = [
        'name' => htmlspecialchars($name, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
        'text' => htmlspecialchars($text, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
        'ts' => time()
    ];
    $current['messages'] = array_slice($messages, -200); // keep last 200

    ftruncate($fp, 0);
    rewind($fp);
    fwrite($fp, json_encode($current, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    fflush($fp);
    flock($fp, LOCK_UN);
    fclose($fp);

    echo json_encode(['ok' => true, 'chat' => $current]);
    exit;
}

echo json_encode(['ok' => false, 'error' => 'requête invalide']);


