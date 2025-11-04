<?php
$roomId = isset($_GET['room']) ? preg_replace('/[^a-zA-Z0-9_-]/', '', $_GET['room']) : '';
if ($roomId === '') {
    header('Location: index.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="fr"> 
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>SYC — Salle <?php echo htmlspecialchars($roomId); ?></title>
    <link rel="stylesheet" href="assets/styles.css">
    <!-- CodeMirror CDN -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/codemirror@5.65.16/lib/codemirror.css">
    <script src="https://cdn.jsdelivr.net/npm/codemirror@5.65.16/lib/codemirror.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/codemirror@5.65.16/mode/javascript/javascript.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/codemirror@5.65.16/mode/php/php.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/codemirror@5.65.16/mode/css/css.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/codemirror@5.65.16/mode/xml/xml.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/codemirror@5.65.16/mode/htmlmixed/htmlmixed.js"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/codemirror@5.65.16/theme/material.css">
</head>
<body>
    <div class="container room">
        <header class="room-header">
            <div class="left">
                <a class="logo" href="index.php">SYC</a>
                <span class="room-id">Salle: <strong id="room-id-label"><?php echo htmlspecialchars($roomId); ?></strong></span>
                <button id="copy-link" class="btn small">Copier le lien</button>
                <button id="copy-readonly-link" class="btn small">Lien lecture seule</button>
            </div>
            <div class="right">
                <input id="filename" class="input small" placeholder="nom de fichier (ex: script.js)" />
                <select id="language" class="input small">
                    <option value="javascript">JavaScript</option>
                    <option value="htmlmixed">HTML</option>
                    <option value="css">CSS</option>
                    <option value="php">PHP</option>
                </select>
                <select id="theme" class="input small">
                    <option value="default">Clair</option>
                    <option value="material">Sombre (Material)</option>
                </select>
                <button id="download-code" class="btn small">Télécharger</button>
            </div>
        </header>

        <main class="split">
            <section class="editor-pane">
                <textarea id="editor" name="editor"></textarea>
            </section>
            <aside class="chat-pane">
                <div id="chat-log" class="chat-log"></div>
                <form id="chat-form" class="row">
                    <input id="chat-name" class="input small" placeholder="Nom" required>
                    <input id="chat-text" class="input" placeholder="Message" required>
                    <button class="btn" type="submit">Envoyer</button>
                </form>
            </aside>
        </main>
    </div>

    <script>
    window.SYC_ROOM_ID = <?php echo json_encode($roomId); ?>;
    window.SYC_READONLY = <?php echo isset($_GET['readonly']) && $_GET['readonly'] === '1' ? 'true' : 'false'; ?>;
    </script>
    <script src="assets/app.js"></script>
</body>
</html>


