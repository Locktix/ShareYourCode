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

function roomFilePath(string $roomId): string {
    return __DIR__ . '/../data/room_' . $roomId . '.json';
}

if ($action === 'create') {
    if ($roomId === '') {
        echo json_encode(['ok' => false, 'error' => 'roomId requis']);
        exit;
    }
    $path = roomFilePath($roomId);
    if (!file_exists($path)) {
        $initial = [
            'roomId' => $roomId,
            'revision' => 1,
            'code' => "// Bienvenue sur SYC (" . $roomId . ")\n",
            'filename' => 'snippet.js',
            'language' => 'javascript',
            'updatedAt' => time()
        ];
        file_put_contents($path, json_encode($initial, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }
    echo json_encode(['ok' => true, 'roomId' => $roomId]);
    exit;
}

if ($roomId === '') {
    echo json_encode(['ok' => false, 'error' => 'roomId requis']);
    exit;
}

$path = roomFilePath($roomId);
if (!file_exists($path)) {
    // Auto-create on first access to allow joining non-existing room IDs
    $initial = [
        'roomId' => $roomId,
        'revision' => 1,
        'code' => "// Nouvelle salle SYC (" . $roomId . ")\n",
        'filename' => 'snippet.js',
        'language' => 'javascript',
        'updatedAt' => time()
    ];
    file_put_contents($path, json_encode($initial, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
}

if ($_SERVER['REQUEST_METHOD'] === 'GET' || $action === 'get') {
    $content = json_decode(file_get_contents($path) ?: '{}', true);
    echo json_encode(['ok' => true, 'room' => $content]);
    exit;
}

// POST update: expects { code, baseRevision }
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $payload = $data;
    $newCode = (string)($payload['code'] ?? '');
    $newFilename = isset($payload['filename']) ? (string)$payload['filename'] : null;
    $newLanguage = isset($payload['language']) ? (string)$payload['language'] : null;
    $baseRev = (int)($payload['baseRevision'] ?? 0);

    $fp = fopen($path, 'c+');
    if ($fp === false) {
        echo json_encode(['ok' => false, 'error' => 'cannot open room file']);
        exit;
    }
    flock($fp, LOCK_EX);
    $currentRaw = stream_get_contents($fp);
    $current = json_decode($currentRaw ?: '{}', true);
    if (!is_array($current)) $current = [];
    $currentRev = (int)($current['revision'] ?? 1);

    if ($baseRev !== 0 && $baseRev !== $currentRev) {
        // Conflict; return latest so client can rebase
        flock($fp, LOCK_UN);
        fclose($fp);
        echo json_encode(['ok' => false, 'conflict' => true, 'room' => $current]);
        exit;
    }

    $updated = [
        'roomId' => $roomId,
        'revision' => $currentRev + 1,
        'code' => $newCode,
        'filename' => $newFilename !== null ? $newFilename : ($current['filename'] ?? 'snippet.js'),
        'language' => $newLanguage !== null ? $newLanguage : ($current['language'] ?? 'javascript'),
        'updatedAt' => time()
    ];

    ftruncate($fp, 0);
    rewind($fp);
    fwrite($fp, json_encode($updated, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    fflush($fp);
    flock($fp, LOCK_UN);
    fclose($fp);

    echo json_encode(['ok' => true, 'room' => $updated]);
    exit;
}

echo json_encode(['ok' => false, 'error' => 'requête invalide']);


