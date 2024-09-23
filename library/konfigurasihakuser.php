<?php


/**
 * @return string[] 
 */
function getDaftarHak()
{
    return [
        'Edit' => 'EDIT',
        'Delete' => 'DELETE',
        'Excel' => 'GENERATE EXCEL',
        'PrintP' => 'PRINT POTRAIT',
        'PrintL' => 'PRINT LANDSCAPE',
        'ShowHarga' => 'SHOW HARGA',
    ];
}

function encodeDaftarHak(array $daftar)
{
    return json_encode($daftar);
}

function parseDaftarHak(string $daftar)
{
    $parse = json_decode($daftar, true);
    $hak = getDaftarHak();

    $output = [];

    foreach ($hak as $index => $value) {
        if (in_array($index, $parse)) {
            $output[$index] = $value;
        }
    }

    return $output;
}
