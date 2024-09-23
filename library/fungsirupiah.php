<?php

function ubahToInt($angka)
{

    if (preg_match('/[,]/', $angka)) {
        [$nominal, $desimal] = explode(',', $angka);
        $formattedNominal = join('', explode('.', $nominal));

        $output = floatval($formattedNominal . '.' . $desimal);
    } else {
        $formattedNominal = join('', explode('.', $angka));
        $output = intval($formattedNominal);
    }

    return $output;
}

function ubahToRp($angka)
{
    if ($angka) {
        $angkaBaru = number_format($angka, 0, ',', '.');
        return $angkaBaru;
    } else {
        return $angka;
    }
}

function ubahToRupiahDesimal($angka)
{
    if (!is_null($angka)) {
        $str = (string) floatval($angka);

        if (preg_match('/[.]/', $str)) {
            [$nominal, $desimal] = explode('.', $str);
            return number_format($str, strlen($desimal), ',', '.');
        } else {
            return number_format($str, 0, ',', '.');
        }
    } else {
        return '-';
    }
}



function penyebut($nilai)
{
    $nilai = abs($nilai);
    $huruf = array("", "Satu", "Dua", "Tiga", "Empat", "Lima", "Enam", "Tujuh", "Delapan", "Sembilan", "Sepuluh", "Sebelas");
    $temp = "";
    if ($nilai < 12) {
        $temp = " " . $huruf[$nilai];
    } else if ($nilai < 20) {
        $temp = penyebut($nilai - 10) . " Belas";
    } else if ($nilai < 100) {
        $temp = penyebut($nilai / 10) . " Puluh" . penyebut($nilai % 10);
    } else if ($nilai < 200) {
        $temp = " Seratus" . penyebut($nilai - 100);
    } else if ($nilai < 1000) {
        $temp = penyebut($nilai / 100) . " Ratus" . penyebut($nilai % 100);
    } else if ($nilai < 2000) {
        $temp = " Seribu" . penyebut($nilai - 1000);
    } else if ($nilai < 1000000) {
        $temp = penyebut($nilai / 1000) . " Ribu" . penyebut($nilai % 1000);
    } else if ($nilai < 1000000000) {
        $temp = penyebut($nilai / 1000000) . " Juta" . penyebut($nilai % 1000000);
    } else if ($nilai < 1000000000000) {
        $temp = penyebut($nilai / 1000000000) . " Milyar" . penyebut(fmod($nilai, 1000000000));
    } else if ($nilai < 1000000000000000) {
        $temp = penyebut($nilai / 1000000000000) . " Trilyun" . penyebut(fmod($nilai, 1000000000000));
    }
    return $temp;
}

function terbilang($nilai)
{
    if ($nilai < 0) {
        $hasil = "minus " . trim(penyebut($nilai));
    } else {
        $hasil = trim(penyebut($nilai));
    }
    return $hasil;
}

function cekDesimal($angka)
{
    if (!is_null($angka)) {
        $str = (string) floatval($angka);
        $array_str = preg_split('/[.]/', $str);

        if (isset($array_str[1])) {
            return join(',', $array_str);
        } else {
            return intval($angka);
        }
    } else {
        return '-';
    }
}

function getDiskon($total, $nilaiDiskon, bool $statusPembulatan = true)
{
    if (is_null($total) || is_null($nilaiDiskon)) {
        return '-';
    } else {
        if (intval($total) === 0) {
            return 0;
        } else {
            $diskon = (floatval($nilaiDiskon) / intval($total)) * 100;
            if ($statusPembulatan === true) {
                $round_diskon = round($diskon * 100) / 100;
                return $round_diskon;
            } else {
                return $diskon;
            }
        }
    }
}
