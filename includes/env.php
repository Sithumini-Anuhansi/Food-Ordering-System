<?php
/**
 * Minimal .env file loader.
 *
 * Reads KEY=VALUE pairs from a .env file and exposes them via getenv()/$_ENV.
 * Real server/environment variables always take precedence over .env values,
 * so this is safe to use in both local dev and production (where secrets are
 * usually injected as real environment variables instead of a file).
 */

if (!function_exists('loadEnv')) {
    function loadEnv($path)
    {
        static $loaded = false;
        if ($loaded) {
            return; // already parsed once during this request
        }
        $loaded = true;

        if (!is_readable($path)) {
            return; // no .env file present (fine in prod if real env vars are set)
        }

        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            $line = trim($line);

            // Skip comments
            if ($line === '' || strpos($line, '#') === 0) {
                continue;
            }

            if (strpos($line, '=') === false) {
                continue; // malformed line, ignore
            }

            list($name, $value) = explode('=', $line, 2);
            $name = trim($name);
            $value = trim($value);

            // Strip matching surrounding quotes, if any
            if (strlen($value) >= 2) {
                $first = $value[0];
                $last = $value[strlen($value) - 1];
                if (($first === '"' && $last === '"') || ($first === "'" && $last === "'")) {
                    $value = substr($value, 1, -1);
                }
            }

            // Don't override real environment variables already set on the server
            if (getenv($name) === false) {
                putenv("$name=$value");
                $_ENV[$name] = $value;
            }
        }
    }
}

if (!function_exists('env')) {
    /** Convenience getter: env('DB_HOST', 'localhost') */
    function env($key, $default = null)
    {
        $value = getenv($key);
        return ($value === false || $value === '') ? $default : $value;
    }
}
