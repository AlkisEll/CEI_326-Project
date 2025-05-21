<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['lang'])) {
    $_SESSION['lang'] = 'en';
}

ob_start(function ($buffer) {
    if ($_SESSION['lang'] === 'el') {
        return translateWithDeepL($buffer, 'EL');
    }
    return $buffer;
});

function translateWithDeepL($html, $targetLang) {
    $apiKey = 'c5eee18e-2084-4356-97dd-a7e7a65fde7b;
    $url = 'https://api.deepl.com/v2/translate';

    $data = http_build_query([
        'auth_key' => $apiKey,
        'text' => $html,
        'target_lang' => $targetLang,
        'tag_handling' => 'html',          // ✅ Preserve HTML tags
        'ignore_tags' => 'script,style',   // ✅ Don't translate JS or CSS
    ]);

    $opts = [
        'http' => [
            'method' => 'POST',
            'header' => "Content-Type: application/x-www-form-urlencoded",
            'content' => $data,
            'timeout' => 10,
        ]
    ];

    $context = stream_context_create($opts);
    $result = file_get_contents($url, false, $context);

    if ($result === FALSE) return $html;

    $json = json_decode($result, true);
    return $json['translations'][0]['text'] ?? $html;
}