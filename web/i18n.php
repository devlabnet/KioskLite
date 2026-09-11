<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
// 1. Define supported languages
$allowed_locales = ['en', 'fr'];
$default_locale  = 'en';

// 2. Determine chosen language (URL parameter query string takes priority)
if (isset($_GET['lang']) && in_array($_GET['lang'], $allowed_locales)) {
    $locale = $_GET['lang'];
    $_SESSION['lang'] = $locale; // Save to session
    setcookie('lang', $locale, time() + (86400 * 30), "/"); // Save to cookie for 30 days
} elseif (isset($_SESSION['lang'])) {
    $locale = $_SESSION['lang'];
} elseif (isset($_COOKIE['lang']) && in_array($_COOKIE['lang'], $allowed_locales)) {
    $locale = $_COOKIE['lang'];
} else {
    // Fallback to browser language if available, otherwise use default
    $browser_lang = substr($_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? '', 0, 2);
    $locale = in_array($browser_lang, $allowed_locales) ? $browser_lang : $default_locale;
}

// 3. Load translation files (and load fallback array to prevent blank gaps)
$translations = include __DIR__ . "/lang/{$locale}.php";
$fallback_translations = ($locale !== $default_locale) ? include __DIR__ . "/lang/{$default_locale}.php" : [];

/**
 * Global translation helper function
 * Supports dynamic value injection using sprintf syntax
 */
function __($key, ...$args) {
    global $translations, $fallback_translations;
    
    // Find key in current locale, drop back to fallback locale, or output raw key string
    $string = $translations[$key] ?? $fallback_translations[$key] ?? $key;
    
    // If additional arguments are passed, format the string dynamically
    if (!empty($args)) {
        return sprintf($string, ...$args);
    }
    
    return $string;
}
