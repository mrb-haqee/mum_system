<?php
include_once BASE_URL_PHP . ASSETS_DIR . '/phpqrcode/qrlib.php';

function generateQRValidator(string $text, string $filename)
{
    $folder = 'qr_validator';
    QRcode::png($text, ABS_PATH_FILE_UPLOAD_DIR . DIRECTORY_SEPARATOR . $folder . DIRECTORY_SEPARATOR . $filename . '.png', QR_ECLEVEL_M, 3, 2, true);
}
