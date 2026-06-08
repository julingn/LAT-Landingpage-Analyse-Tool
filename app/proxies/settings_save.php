<?php
session_start();
if (empty($_SESSION['logged_in'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Nicht authentifiziert']);
    exit;
}

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

// CSRF check
$csrfToken = $_POST['csrf_token'] ?? '';
if (empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $csrfToken)) {
    http_response_code(403);
    echo json_encode(['error' => 'CSRF-Validierung fehlgeschlagen']);
    exit;
}

$sf = __DIR__ . '/settings.json';
$settings = [];
if (file_exists($sf)) {
    $settings = json_decode(file_get_contents($sf), true) ?? [];
}

$action = $_POST['action'] ?? '';
$errors = [];

if ($action === 'save_api_key') {
    $key = trim($_POST['anthropic_api_key'] ?? '');
    if (!empty($key) && !str_starts_with($key, 'sk-ant-')) {
        $errors[] = 'API-Key muss mit "sk-ant-" beginnen.';
    } else {
        $settings['anthropic_api_key'] = $key;
    }
}

if ($action === 'save_password') {
    $pw  = $_POST['new_password'] ?? '';
    $pw2 = $_POST['confirm_password'] ?? '';

    if (strlen($pw) < 8) {
        $errors[] = 'Passwort muss mindestens 8 Zeichen lang sein.';
    } elseif ($pw !== $pw2) {
        $errors[] = 'Passwörter stimmen nicht überein.';
    } else {
        $settings['login_password_hash'] = password_hash($pw, PASSWORD_DEFAULT);
    }
}

if ($action === 'save_model') {
    $allowed = ['claude-sonnet-4-5', 'claude-opus-4-5', 'claude-haiku-4-5', 'claude-opus-4-0', 'claude-sonnet-4-0'];
    $model = $_POST['ai_model'] ?? 'claude-sonnet-4-5';
    if (in_array($model, $allowed, true)) {
        $settings['ai_model'] = $model;
    }
}

if ($action === 'save_openai') {
    $key   = trim($_POST['openai_api_key'] ?? '');
    $model = trim($_POST['openai_model'] ?? '');
    if (!empty($key) && !str_starts_with($key, 'sk-')) {
        $errors[] = 'OpenAI API-Key muss mit "sk-" beginnen.';
    } else {
        if (!empty($key)) $settings['openai_api_key'] = $key;
        if (!empty($model)) $settings['openai_model'] = $model;
    }
}

if ($action === 'save_dataforseo') {
    $login    = trim($_POST['dataforseo_login'] ?? '');
    $password = trim($_POST['dataforseo_password'] ?? '');
    if (!empty($login) && !filter_var($login, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'DataForSEO Login muss eine gültige E-Mail-Adresse sein.';
    } else {
        if (!empty($login))    $settings['dataforseo_login']    = $login;
        if (!empty($password)) $settings['dataforseo_password'] = $password;
    }
}

if ($action === 'save_pagespeed') {
    $key = trim($_POST['pagespeed_api_key'] ?? '');
    // PageSpeed Key hat kein festes Präfix — nur Länge prüfen
    if (!empty($key) && strlen($key) < 10) {
        $errors[] = 'PageSpeed API-Key scheint ungültig zu sein.';
    } else {
        $settings['pagespeed_api_key'] = $key;
    }
}

if ($action === 'save_gsc') {
    $siteUrl = trim($_POST['gsc_site_url'] ?? '');
    $saJson  = trim($_POST['gsc_service_account_json'] ?? '');
    if (!empty($siteUrl)) {
        $settings['gsc_site_url'] = $siteUrl;
    }
    if (!empty($saJson)) {
        // Validierung: muss gültiges JSON mit den Pflichtfeldern sein
        $sa = json_decode($saJson, true);
        if (!is_array($sa) || empty($sa['client_email']) || empty($sa['private_key'])) {
            $errors[] = 'Service-Account-JSON ungültig. Pflichtfelder: client_email, private_key.';
        } else {
            $settings['gsc_service_account_json'] = $saJson;
        }
    }
}

if ($action === 'save_sistrix') {
    $key = trim($_POST['sistrix_api_key'] ?? '');
    if (!empty($key) && strlen($key) < 8) {
        $errors[] = 'Sistrix API-Key scheint ungültig zu sein.';
    } else {
        if (!empty($key)) $settings['sistrix_api_key'] = $key;
    }
}

if (!empty($errors)) {
    http_response_code(422);
    echo json_encode(['error' => implode(' ', $errors)]);
    exit;
}

// Write settings
$written = file_put_contents($sf, json_encode($settings, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
if ($written === false) {
    http_response_code(500);
    echo json_encode(['error' => 'Einstellungen konnten nicht gespeichert werden. Schreibrechte prüfen.']);
    exit;
}

echo json_encode(['success' => true]);
