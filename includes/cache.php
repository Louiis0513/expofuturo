<?php
declare(strict_types=1);

/**
 * Caché en archivos para hosting compartido (sin Redis/Memcached).
 * - TTL largo (4 h) como resync de seguridad.
 * - Escritura atómica (tmp + rename).
 * - Preferir parche (write-through) frente a invalidar todo.
 */
const CACHE_DIR = __DIR__ . '/../cache';
const CACHE_TTL_DEFAULT = 14400;     // 4 h — catálogo / home / ficha
const CACHE_TTL_AREAS = 14400;       // 4 h
const CACHE_TTL_CARRERA = 14400;     // 4 h
const CACHE_TTL_NEGATIVO = 300;      // 5 min — miss (carrera no existe)

function cacheDir(): string
{
    if (!is_dir(CACHE_DIR)) {
        @mkdir(CACHE_DIR, 0775, true);
    }
    return CACHE_DIR;
}

function cachePath(string $key): string
{
    $safe = preg_replace('/[^a-zA-Z0-9._-]+/', '_', $key) ?: 'key';
    return cacheDir() . '/' . $safe . '.cache';
}

function cacheGet(string $key): mixed
{
    $path = cachePath($key);
    if (!is_file($path)) {
        return null;
    }
    $raw = @file_get_contents($path);
    if ($raw === false || $raw === '') {
        return null;
    }
    $payload = @unserialize($raw, ['allowed_classes' => false]);
    if (!is_array($payload) || !array_key_exists('expires', $payload) || !array_key_exists('data', $payload)) {
        @unlink($path);
        return null;
    }
    if ((int) $payload['expires'] < time()) {
        @unlink($path);
        return null;
    }
    return $payload['data'];
}

/**
 * Lee data + TTL restante sin recrear si está vencido (null si no hay archivo válido).
 * @return array{data:mixed,ttl:int}|null
 */
function cachePeek(string $key): ?array
{
    $path = cachePath($key);
    if (!is_file($path)) {
        return null;
    }
    $raw = @file_get_contents($path);
    if ($raw === false || $raw === '') {
        return null;
    }
    $payload = @unserialize($raw, ['allowed_classes' => false]);
    if (!is_array($payload) || !array_key_exists('expires', $payload) || !array_key_exists('data', $payload)) {
        @unlink($path);
        return null;
    }
    $ttl = (int) $payload['expires'] - time();
    if ($ttl < 1) {
        @unlink($path);
        return null;
    }
    return ['data' => $payload['data'], 'ttl' => $ttl];
}

function cacheSet(string $key, mixed $data, int $ttl = CACHE_TTL_DEFAULT): void
{
    $path = cachePath($key);
    $payload = serialize([
        'expires' => time() + max(1, $ttl),
        'data' => $data,
    ]);
    $tmp = $path . '.' . bin2hex(random_bytes(4)) . '.tmp';
    if (@file_put_contents($tmp, $payload, LOCK_EX) === false) {
        return;
    }
    if (!@rename($tmp, $path)) {
        @unlink($tmp);
        @file_put_contents($path, $payload, LOCK_EX);
    }
}

/** Actualiza data y renueva TTL al valor dado (por defecto 4 h). */
function cachePatch(string $key, mixed $data, int $ttl = CACHE_TTL_DEFAULT): void
{
    cacheSet($key, $data, $ttl);
}

/**
 * Si la clave existe y es válida, aplica $fn(data) y guarda.
 * No crea la clave si no existe (lazy: el público la genera).
 * @param callable(mixed):mixed $fn
 */
function cachePatchIfPresent(string $key, callable $fn, int $ttl = CACHE_TTL_DEFAULT): void
{
    $peek = cachePeek($key);
    if ($peek === null) {
        return;
    }
    $nuevo = $fn($peek['data']);
    cacheSet($key, $nuevo, $ttl);
}

function cacheForget(string $key): void
{
    $path = cachePath($key);
    if (is_file($path)) {
        @unlink($path);
    }
}

function cacheForgetPrefix(string $prefix): void
{
    $dir = cacheDir();
    $safe = preg_replace('/[^a-zA-Z0-9._-]+/', '_', $prefix) ?: '';
    if ($safe === '' || !is_dir($dir)) {
        return;
    }
    foreach (glob($dir . '/' . $safe . '*.cache') ?: [] as $file) {
        @unlink($file);
    }
}

/**
 * Claves lógicas existentes con un prefijo (sin extensión).
 * @return list<string>
 */
function cacheKeysByPrefix(string $prefix): array
{
    $dir = cacheDir();
    $safe = preg_replace('/[^a-zA-Z0-9._-]+/', '_', $prefix) ?: '';
    if ($safe === '' || !is_dir($dir)) {
        return [];
    }
    $keys = [];
    foreach (glob($dir . '/' . $safe . '*.cache') ?: [] as $file) {
        $base = basename($file, '.cache');
        if ($base !== '') {
            $keys[] = $base;
        }
    }
    return $keys;
}

/** Clave de catálogo: catalogo_a{area}_c_{all|md5}_t_{all|tipo}. */
function cacheCatalogoKey(int $idArea = 0, string $ciudad = '', string $tipo = ''): string
{
    $ciudad = trim($ciudad);
    $tipo = ($tipo === 'publica' || $tipo === 'privada') ? $tipo : '';
    return 'catalogo_a' . $idArea
        . '_c_' . ($ciudad !== '' ? md5($ciudad) : 'all')
        . '_t_' . ($tipo !== '' ? $tipo : 'all');
}

/**
 * Invalidación total (resync de emergencia). Preferir funciones cacheParche* .
 */
function cacheInvalidarCatalogo(): void
{
    cacheForget('areas');
    cacheForget('ciudades');
    cacheForget('stats');
    cacheForget('home_destacadas');
    cacheForget('catalogo_filtros_idx');
    cacheForgetPrefix('catalogo_');
    cacheForgetPrefix('carrera_');
}

/** Registra ciudad usada en filtros (para poder parchear esas claves). */
function cacheRegistrarFiltroCiudad(string $ciudad): void
{
    $ciudad = trim($ciudad);
    if ($ciudad === '') {
        return;
    }
    $hash = md5($ciudad);
    $idx = cacheGet('catalogo_filtros_idx');
    if (!is_array($idx)) {
        $idx = [];
    }
    if (isset($idx[$hash]) && $idx[$hash] === $ciudad) {
        return;
    }
    $idx[$hash] = $ciudad;
    cacheSet('catalogo_filtros_idx', $idx, CACHE_TTL_DEFAULT);
}

/** @return array<string, string> hash => ciudad */
function cacheFiltrosCiudadConocidos(): array
{
    $idx = cacheGet('catalogo_filtros_idx');
    return is_array($idx) ? $idx : [];
}
