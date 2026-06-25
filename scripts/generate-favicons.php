<?php

/**
 * Generate favicons from public/images/aretia-logo.png (icon crop + white background).
 */
$root = dirname(__DIR__);
$srcPath = $root.'/public/images/aretia-logo.png';

if (! is_file($srcPath)) {
    fwrite(STDERR, "Logo not found: {$srcPath}\n");
    exit(1);
}

$src = imagecreatefrompng($srcPath);
if ($src === false) {
    fwrite(STDERR, "Could not load logo PNG.\n");
    exit(1);
}

$height = imagesy($src);
$crop = min($height, (int) round($height * 1.02));

function makeFavicon(string $srcPath, int $crop, int $size, string $outPath): void
{
    $src = imagecreatefrompng($srcPath);
    $dst = imagecreatetruecolor($size, $size);
    imagealphablending($dst, false);
    imagesavealpha($dst, true);

    $white = imagecolorallocate($dst, 255, 255, 255);
    imagefilledrectangle($dst, 0, 0, $size, $size, $white);

    imagealphablending($dst, true);
    imagecopyresampled($dst, $src, 0, 0, 0, 0, $size, $size, $crop, $crop);
    imagealphablending($dst, false);
    imagesavealpha($dst, false);

    imagepng($dst, $outPath);
    imagedestroy($dst);
    imagedestroy($src);
}

$public = $root.'/public';
$sizes = [
    16 => $public.'/favicon-16x16.png',
    32 => $public.'/favicon-32x32.png',
    48 => $public.'/favicon-48x48.png',
    180 => $public.'/apple-touch-icon.png',
    192 => $public.'/android-chrome-192x192.png',
];

foreach ($sizes as $size => $path) {
    makeFavicon($srcPath, $crop, $size, $path);
    echo "Wrote {$path}\n";
}

imagedestroy($src);

// Real .ico with 16 + 32 layers (PNG-in-ICO for broad browser support).
$icoPath = $public.'/favicon.ico';
$png32 = $public.'/favicon-32x32.png';
$png16 = $public.'/favicon-16x16.png';

if (! writeIco($icoPath, [$png16, $png32])) {
    copy($png32, $icoPath);
    echo "Wrote {$icoPath} (png copy fallback)\n";
} else {
    echo "Wrote {$icoPath}\n";
}

/**
 * @param  list<string>  $pngPaths
 */
function writeIco(string $outPath, array $pngPaths): bool
{
    $images = [];
    foreach ($pngPaths as $path) {
        $data = file_get_contents($path);
        if ($data === false) {
            return false;
        }
        $images[] = $data;
    }

    $count = count($images);
    $header = pack('vvv', 0, 1, $count);
    $dir = '';
    $offset = 6 + (16 * $count);
    $blob = '';

    foreach ($images as $data) {
        $size = getimagesizefromstring($data);
        if ($size === false) {
            return false;
        }
        [$w, $h] = $size;
        $w = $w >= 256 ? 0 : $w;
        $h = $h >= 256 ? 0 : $h;
        $len = strlen($data);
        $dir .= pack('CCCCvvVV', $w, $h, 0, 0, 1, 32, $len, $offset);
        $blob .= $data;
        $offset += $len;
    }

    return file_put_contents($outPath, $header.$dir.$blob) !== false;
}
