<?php
declare(strict_types=1);

namespace App\Models;

use finfo;
use InvalidArgumentException;

/**
 * Guarda las fotos de los productos en uploads/menu/ con un nombre aleatorio.
 * Solo acepta imágenes reales (se revisa el contenido, no la extensión del archivo).
 */
final class PhotoStorage
{
    public const MAX_BYTES = 5 * 1024 * 1024;
    private const FOLDER = 'uploads/menu';
    private const TYPES = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
        'image/gif' => 'gif',
    ];

    public static function wasSent(array $file): bool
    {
        return isset($file['error']) && $file['error'] !== UPLOAD_ERR_NO_FILE;
    }

    /**
     * @return string Ruta relativa para guardar en la base de datos, p. ej. uploads/menu/ab12….jpg
     */
    public function store(array $file): string
    {
        $error = $file['error'] ?? UPLOAD_ERR_NO_FILE;
        if ($error === UPLOAD_ERR_INI_SIZE || $error === UPLOAD_ERR_FORM_SIZE
            || ($file['size'] ?? 0) > self::MAX_BYTES) {
            throw new InvalidArgumentException('La foto es muy pesada (máximo 5 MB).');
        }
        if ($error !== UPLOAD_ERR_OK || !is_uploaded_file((string) ($file['tmp_name'] ?? ''))) {
            throw new InvalidArgumentException('No se pudo subir la foto. Inténtalo de nuevo.');
        }

        $mime = (new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
        if (!isset(self::TYPES[$mime]) || getimagesize($file['tmp_name']) === false) {
            throw new InvalidArgumentException('La foto debe ser una imagen JPG, PNG, WebP o GIF.');
        }

        $folder = $this->absolute(self::FOLDER);
        if (!is_dir($folder) && !mkdir($folder, 0755, true) && !is_dir($folder)) {
            throw new InvalidArgumentException('No se pudo crear la carpeta de fotos en el servidor.');
        }

        $path = self::FOLDER . '/' . bin2hex(random_bytes(16)) . '.' . self::TYPES[$mime];
        if (!move_uploaded_file($file['tmp_name'], $this->absolute($path))) {
            throw new InvalidArgumentException('No se pudo guardar la foto en el servidor.');
        }

        return $path;
    }

    public static function isLocal(?string $path): bool
    {
        return $path !== null && preg_match('#\Auploads/menu/[a-f0-9]{32}\.(jpg|png|webp|gif)\z#', $path) === 1;
    }

    public function delete(?string $path): void
    {
        if (self::isLocal($path) && is_file($this->absolute($path))) {
            unlink($this->absolute($path));
        }
    }

    private function absolute(string $relative): string
    {
        return dirname(__DIR__) . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relative);
    }
}
