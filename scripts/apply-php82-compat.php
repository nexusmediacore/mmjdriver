<?php

/**
 * Patch kompatibilitas PHP 8.2 untuk project ini.
 *
 * symfony/http-foundation v8 mensyaratkan PHP >= 8.4 dan memanggil fungsi
 * request_parse_body() tanpa penjaga function_exists(), sehingga aplikasi
 * langsung fatal error di PHP 8.2. Project ini sengaja dijalankan di PHP 8.2
 * (dipakai bersama project lain), jadi patch ini dibuat OTOMATIS.
 *
 * Pengaman:
 *   - Idempoten  : aman dijalankan berulang, tidak akan menumpuk perubahan.
 *   - Backup     : salinan file asli dibuat di vendor/.../*.php82-orig
 *   - Validasi   : setelah patch, `php -l` dijalankan; bila sintaks rusak
 *                  file OTOMATIS dikembalikan dari backup.
 *
 * Dijalankan otomatis oleh Composer pada install/update/dump-autoload
 * melalui hook "post-autoload-dump". Manual: php scripts/apply-php82-compat.php
 */
$root = dirname(__DIR__);
$target = 'vendor/symfony/http-foundation/Request.php';
$fullPath = $root.'/'.$target;
$backupPath = $fullPath.'.php82-orig';

/**
 * Cek sintaks PHP sebuah file.
 */
function syntaxOk(string $file): bool
{
    $php = PHP_BINARY;
    $out = shell_exec(escapeshellcmd($php).' -l '.escapeshellarg($file).' 2>&1');

    return $out !== null && str_contains($out, 'No syntax errors');
}

if (! file_exists($fullPath)) {
    echo "  - {$target} : belum terinstal, dilewati.\n";
    exit(0);
}

// 1) Sudah dipatch? -> tidak melakukan apa pun
$source = file_get_contents($fullPath);
if (str_contains($source, "function_exists('request_parse_body')")) {
    if (! syntaxOk($fullPath)) {
        echo "  ! {$target} : sudah dipatch TAPI sintaks rusak -> memulihkan.\n";
        if (file_exists($backupPath)) {
            copy($backupPath, $fullPath);
            exit(patchRequest($fullPath, $backupPath, $target) ? 0 : 1);
        }
        exit(1);
    }

    echo "  = {$target} : sudah dipatch & sintaks valid, dilewati.\n";
    exit(0);
}

// 2) Terapkan patch, lalu validasi
exit(patchRequest($fullPath, $backupPath, $target) ? 0 : 1);

/**
 * Terapkan patch pada Request.php dengan jaring pengaman backup.
 */
function patchRequest(string $fullPath, string $backupPath, string $target): bool
{
    $source = file_get_contents($fullPath);

    if (! file_exists($backupPath)) {
        copy($fullPath, $backupPath);
        echo '  * backup dibuat: '.basename($backupPath)."\n";
    }

    /*
     * Blok asli Symfony (indentasi bebas, ditangani regex):
     *
     *     try {
     *         [$post, $files] = request_parse_body();
     *     } catch (\RequestParseBodyException) {
     *         $post = $_POST;
     *         $files = $_FILES;
     *     }
     */
    $pattern = '/try\s*\{\s*\[\$post,\s*\$files\]\s*=\s*request_parse_body\(\);\s*\}\s*catch\s*\(\s*\\\\?RequestParseBodyException\s*\)\s*\{\s*\$post\s*=\s*\$_POST;\s*\$files\s*=\s*\$_FILES;\s*\}/s';

    if (! preg_match($pattern, $source)) {
        echo "  ! {$target} : pola tidak ditemukan (versi berbeda?).\n";

        return false;
    }

    $replacement = <<<'PHP'
$post = $_POST;
        $files = $_FILES;

        // Kompatibilitas PHP 8.2: request_parse_body() baru ada di PHP 8.4+
        if (\function_exists('request_parse_body')) {
            try {
                [$post, $files] = request_parse_body();
            } catch (\RequestParseBodyException) {
                $post = $_POST;
                $files = $_FILES;
            }
        }
PHP;

    $updated = preg_replace($pattern, $replacement, $source, 1);

    if ($updated === null || $updated === $source) {
        echo "  ! {$target} : penggantian gagal.\n";

        return false;
    }

    file_put_contents($fullPath, $updated);

    // 3) Validasi sintaks; bila rusak, kembalikan dari backup
    if (! syntaxOk($fullPath)) {
        echo "  ! {$target} : sintaks rusak setelah patch -> memulihkan dari backup.\n";
        copy($backupPath, $fullPath);

        return false;
    }

    echo "  + {$target} : berhasil dipatch & sintaks valid.\n";
    echo "\nAplikasi kini kompatibel dengan PHP 8.2.\n";

    return true;
}
