<?php
/**
 * ImageProcessor - conversao e compressao de imagens
 * Usa GD ou Imagick (fallback) conforme disponibilidade no servidor
 */

class ImageProcessor
{
    public static function convertToJpeg(string $sourcePath, string $destPath, int $quality = 75, int $maxWidth = 800, int $maxHeight = 800): void
    {
        if (extension_loaded('gd')) {
            self::convertWithGd($sourcePath, $destPath, $quality, $maxWidth, $maxHeight);
            return;
        }
        if (extension_loaded('imagick')) {
            self::convertWithImagick($sourcePath, $destPath, $quality, $maxWidth, $maxHeight);
            return;
        }
        throw new RuntimeException(
            'Nenhuma biblioteca de imagem disponível. Habilite a extensão GD ou Imagick no PHP (php.ini: extension=gd ou extension=imagick).'
        );
    }

    private static function convertWithImagick(string $sourcePath, string $destPath, int $quality, int $maxWidth, int $maxHeight): void
    {
        try {
            $img = new Imagick($sourcePath);
        } catch (ImagickException $e) {
            throw new InvalidArgumentException('Arquivo de imagem inválido.');
        }

        $w = (int) $img->getImageWidth();
        $h = (int) $img->getImageHeight();
        if ($w <= 0 || $h <= 0) {
            $img->clear();
            throw new InvalidArgumentException('Arquivo de imagem inválido.');
        }

        $ratio = min($maxWidth / $w, $maxHeight / $h, 1);
        $newW = (int) round($w * $ratio);
        $newH = (int) round($h * $ratio);

        $img->resizeImage($newW, $newH, Imagick::FILTER_LANCZOS, 1);
        $img->setImageFormat('jpeg');
        $img->setImageCompressionQuality($quality);

        $destDir = dirname($destPath);
        if (!is_dir($destDir)) {
            mkdir($destDir, 0755, true);
        }

        if (!$img->writeImage($destPath)) {
            $img->clear();
            throw new RuntimeException('Não foi possível salvar a imagem convertida.');
        }
        $img->clear();
    }

    private static function convertWithGd(string $sourcePath, string $destPath, int $quality, int $maxWidth, int $maxHeight): void
    {
        $info = getimagesize($sourcePath);
        if ($info === false) {
            throw new InvalidArgumentException('Arquivo de imagem inválido.');
        }

        [$width, $height, $type] = $info;

        switch ($type) {
            case IMAGETYPE_JPEG:
                $image = imagecreatefromjpeg($sourcePath);
                break;
            case IMAGETYPE_PNG:
                $image = imagecreatefrompng($sourcePath);
                break;
            case IMAGETYPE_GIF:
                $image = imagecreatefromgif($sourcePath);
                break;
            case IMAGETYPE_WEBP:
                $image = @imagecreatefromwebp($sourcePath);
                break;
            default:
                throw new InvalidArgumentException('Formato de imagem não suportado.');
        }

        if (!$image) {
            throw new RuntimeException('Não foi possível carregar a imagem.');
        }

        $ratio = min($maxWidth / $width, $maxHeight / $height, 1);
        $newWidth = (int) round($width * $ratio);
        $newHeight = (int) round($height * $ratio);

        $destImage = imagecreatetruecolor($newWidth, $newHeight);
        $white = imagecolorallocate($destImage, 255, 255, 255);
        imagefill($destImage, 0, 0, $white);

        imagecopyresampled(
            $destImage,
            $image,
            0, 0, 0, 0,
            $newWidth, $newHeight,
            $width, $height
        );

        $destDir = dirname($destPath);
        if (!is_dir($destDir)) {
            mkdir($destDir, 0755, true);
        }

        if (!imagejpeg($destImage, $destPath, $quality)) {
            imagedestroy($image);
            imagedestroy($destImage);
            throw new RuntimeException('Não foi possível salvar a imagem convertida.');
        }

        imagedestroy($image);
        imagedestroy($destImage);
    }
}
