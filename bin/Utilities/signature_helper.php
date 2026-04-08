<?php
require_once APP_ROOT . '/bootstrap.php';

class SignatureHelper
{
    /**
     * Change this to a script/cursive TTF font that exists on your server.
     * Do NOT commit commercial font files to Git if you do not own redistribution rights.
     */
    public const FONTS = [
        APP_ROOT . '/assets/fonts/GreatVibes-Regular.ttf',
        APP_ROOT . '/assets/fonts/Allura-Regular.ttf',
        APP_ROOT . '/assets/fonts/DancingScript-Regular.ttf',
    ];
    
    /**
     * Output folder for generated signature PNGs.
     */
    public const OUTPUT_DIR = APP_ROOT . '/uploads/destruction_signatures';
    
    public static function ensureOutputDir(): void
    {
        if (!is_dir(self::OUTPUT_DIR)) {
            mkdir(self::OUTPUT_DIR, 0775, true);
        }
    }
    
    public static function sanitizeName(string $name): string
    {
        $name = trim($name);
        $name = preg_replace('/\s+/', ' ', $name);
        return $name;
    }
    
    public static function validateName(string $name): bool
    {
        return (bool) preg_match('/^[A-Za-z][A-Za-z\-\.\'\s]{1,98}[A-Za-z\.]$/', $name);
    }
    
    public static function generateSignatureImage(string $fullName, int $recordId, string $role): string
    {
        self::ensureOutputDir();
        
        $fullName = self::sanitizeName($fullName);
        
        if (!self::validateName($fullName)) {
            throw new Exception('Invalid name format for signature.');
        }
        
        if (!extension_loaded('gd')) {
            throw new Exception('PHP GD extension is required for signature generation.');
        }
        
        $fontPath = self::FONTS[array_rand(self::FONTS)];
        if (!file_exists($fontPath)) {
            throw new Exception('Signature font file not found: ' . $fontPath);
        }
        
        $width  = 500;
        $height = 120;
        $fontSize = 30;
        
        if (strpos($fontPath, 'DancingScript') !== false) {
            $fontSize = 26;
        } elseif (strpos($fontPath, 'Allura') !== false) {
            $fontSize = 28;
        }
        $angle = 0;
        
        $image = imagecreatetruecolor($width, $height);
        if (!$image) {
            throw new Exception('Unable to create signature image canvas.');
        }
        
        imagesavealpha($image, true);
        $transparent = imagecolorallocatealpha($image, 0, 0, 0, 127);
        imagefill($image, 0, 0, $transparent);
        
        $ink = imagecolorallocate($image, 20, 20, 20);
        
        $bbox = imagettfbbox($fontSize, $angle, $fontPath, $fullName);
        if ($bbox === false) {
            imagedestroy($image);
            throw new Exception('Unable to calculate text box for signature image.');
        }
        
        $textWidth  = abs($bbox[2] - $bbox[0]);
        $textHeight = abs($bbox[7] - $bbox[1]);
        
        $x = max(10, intval(($width - $textWidth) / 2));
        $y = max(40, intval(($height + $textHeight) / 2));
        
        imagettftext($image, $fontSize, $angle, $x, $y, $ink, $fontPath, $fullName);
        
        $safeRole = preg_replace('/[^a-z0-9_]/i', '_', strtolower($role));
        $filename = sprintf(
            '%s_%d_%s.png',
            $safeRole,
            $recordId,
            date('YmdHis')
            );
        
        $absolutePath = self::OUTPUT_DIR . '/' . $filename;
        $relativePath = 'uploads/destruction_signatures/' . $filename;
        
        if (!imagepng($image, $absolutePath)) {
            imagedestroy($image);
            throw new Exception('Unable to save signature image.');
        }
        
        imagedestroy($image);
        
        return $relativePath;
    }
}