<?php

/**
 * Helper functions (globally available)
 */

if (!function_exists('env')) {
    function env(string $key, $default = null) {
        return $_ENV[$key] ?? $default;
    }
}

if (!function_exists('dd')) {
    function dd(...$vars) {
        foreach ($vars as $var) {
            var_dump($var);
        }
        die(1);
    }
}

if (!function_exists('sanitize_html')) {
    function sanitize_html(string $html): string {
        return htmlspecialchars($html, ENT_QUOTES, 'UTF-8');
    }
}
