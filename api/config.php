<?php

declare(strict_types=1);

function env(string $key, ?string $default = null): ?string
{
    $value = getenv($key);

    if ($value === false || $value === '') {
        return $default;
    }

    return $value;
}

function appUrl(string $path = ''): string
{
    $baseUrl = rtrim((string) env('APP_URL', ''), '/');

    if ($baseUrl === '') {
        return ltrim($path, '/');
    }

    return $baseUrl . '/' . ltrim($path, '/');
}

function publicAppBaseUrl(): string
{
    $configuredBase = normalizeConfiguredBaseUrl((string) env('QR_BASE_URL', (string) env('APP_URL', '')));

    if ($configuredBase !== '') {
        return $configuredBase;
    }

    return rtrim(deriveBaseUrlFromRequest(), '/');
}

function deriveBaseUrlFromRequest(): string
{
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $hostHeader = trim((string) ($_SERVER['HTTP_HOST'] ?? ''));
    $host = 'localhost';
    $port = null;

    if ($hostHeader !== '') {
        $parsedHost = parse_url('http://' . $hostHeader, PHP_URL_HOST);
        $parsedPort = parse_url('http://' . $hostHeader, PHP_URL_PORT);

        if (is_string($parsedHost) && $parsedHost !== '') {
            $host = $parsedHost;
        }

        if (is_int($parsedPort)) {
            $port = $parsedPort;
        }
    }

    if ($port === null) {
        $serverPort = (int) ($_SERVER['SERVER_PORT'] ?? 0);

        if ($serverPort > 0) {
            $port = $serverPort;
        }
    }

    if (in_array($host, ['localhost', '127.0.0.1', '::1'], true)) {
        $lanIp = detectLocalNetworkIpv4();

        if ($lanIp !== null) {
            $host = $lanIp;
        }
    }

    $isDefaultPort = ($scheme === 'https' && $port === 443) || ($scheme === 'http' && $port === 80);
    $portPart = ($port !== null && !$isDefaultPort) ? ':' . $port : '';
    $basePath = rtrim(str_replace('\\', '/', dirname((string) ($_SERVER['SCRIPT_NAME'] ?? '/'))), '/');

    return sprintf('%s://%s%s%s', $scheme, $host, $portPart, $basePath);
}

function detectLocalNetworkIpv4(): ?string
{
    $candidates = [];

    $serverAddr = (string) ($_SERVER['SERVER_ADDR'] ?? '');

    if ($serverAddr !== '') {
        $candidates[] = $serverAddr;
    }

    $hostName = (string) gethostname();

    if ($hostName !== '') {
        $resolved = gethostbynamel($hostName);

        if (is_array($resolved)) {
            $candidates = array_merge($candidates, $resolved);
        }
    }

    $candidates = array_merge($candidates, collectSystemIpv4Candidates());

    $fallbackVirtualBox = null;

    foreach ($candidates as $candidate) {
        $ip = trim((string) $candidate);

        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) === false) {
            continue;
        }

        if ($ip === '127.0.0.1' || str_starts_with($ip, '169.254.')) {
            continue;
        }

        if (str_starts_with($ip, '192.168.56.')) {
            if ($fallbackVirtualBox === null) {
                $fallbackVirtualBox = $ip;
            }

            continue;
        }

        return $ip;
    }

    if ($fallbackVirtualBox !== null) {
        return $fallbackVirtualBox;
    }

    return null;
}

function collectSystemIpv4Candidates(): array
{
    if (!function_exists('shell_exec')) {
        return [];
    }

    $commands = [
        'ipconfig',
        'hostname -I',
        'ip -4 addr',
    ];

    foreach ($commands as $command) {
        $output = @shell_exec($command);

        if (!is_string($output) || trim($output) === '') {
            continue;
        }

        if (preg_match_all('/\b(?:\d{1,3}\.){3}\d{1,3}\b/', $output, $matches) !== 1) {
            continue;
        }

        $ips = [];

        foreach ((array) ($matches[0] ?? []) as $match) {
            $ip = trim((string) $match);

            if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) === false) {
                continue;
            }

            $ips[] = $ip;
        }

        if ($ips !== []) {
            return array_values(array_unique($ips));
        }
    }

    return [];
}

function normalizeConfiguredBaseUrl(string $rawBase): string
{
    $value = trim($rawBase);

    if ($value === '') {
        return '';
    }

    if (!preg_match('#^https?://#i', $value)) {
        $value = 'http://' . ltrim($value, '/');
    }

    $parts = parse_url($value);

    if (!is_array($parts)) {
        return '';
    }

    $host = (string) ($parts['host'] ?? '');
    $path = (string) ($parts['path'] ?? '');

    if ($host === '' && $path !== '') {
        $fixed = normalizeIpWithSlashes($path);

        if ($fixed !== null) {
            $parts['host'] = $fixed['host'];
            $parts['path'] = $fixed['path'];
            $host = $parts['host'];
            $path = $parts['path'];
        }
    }

    if ($host === '') {
        return '';
    }

    $scheme = strtolower((string) ($parts['scheme'] ?? 'http'));
    $port = isset($parts['port']) ? ':' . (int) $parts['port'] : '';
    $path = rtrim($path, '/');

    return $scheme . '://' . $host . $port . $path;
}

function normalizeIpWithSlashes(string $path): ?array
{
    $trimmed = ltrim($path, '/');

    if (!preg_match('#^(\d{1,3})[\./](\d{1,3})[\./](\d{1,3})[\./](\d{1,3})(/.*)?$#', $trimmed, $matches)) {
        return null;
    }

    $octets = [
        (int) $matches[1],
        (int) $matches[2],
        (int) $matches[3],
        (int) $matches[4],
    ];

    foreach ($octets as $octet) {
        if ($octet < 0 || $octet > 255) {
            return null;
        }
    }

    $host = implode('.', $octets);
    $restPath = (string) ($matches[5] ?? '');

    return [
        'host' => $host,
        'path' => $restPath,
    ];
}
