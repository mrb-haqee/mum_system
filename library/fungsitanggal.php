<?php

function tanggalTerbilang($date)
{
    $dateEng = date('j F Y', strtotime($date));
    return $dateEng;
}

function tanggalTerbilangSingkat($date)
{
    $dateEng = date('d/M/Y', strtotime($date));
    return $dateEng;
}

function tanggalTerbilangEng($date)
{
    $dateEng = date('F jS, Y', strtotime($date));
    return $dateEng;
}

function selisihTanggal($startDate, $endDate)
{
    $start_date = new DateTime($startDate);
    $end_date   = new DateTime($endDate);
    $interval   = $start_date->diff($end_date);
    $selisih    = $interval->days;
    return $selisih;
}

function namaHari1($tahun, $bulan, $hari)
{
    $tanggal  = $tahun . "-" . $bulan . "-" . $hari;
    $namaHari = date('D', strtotime($tanggal));
    return $namaHari;
}
function namaHari($tanggal)
{
    $namaHari = date('D', strtotime($tanggal));
    return $namaHari;
}

function namaBulan($bulan, bool $short = true)
{
    $listBulan = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
    if ($short) {
        return substr($listBulan[intval($bulan) - 1], 0, 3);
    } else {
        return $listBulan[intval($bulan) - 1];
    }
}

function waktuBesok($waktuSekarang)
{
    $waktuBesok = date('Y-m-d', strtotime('+1 days', strtotime($waktuSekarang)));
    return $waktuBesok;
}

function waktuKemarin($waktuSekarang)
{
    $waktuKemarin = date('Y-m-d', strtotime('-1 days', strtotime($waktuSekarang)));
    return $waktuKemarin;
}


function waktuBulanLalu($waktuSekarang)
{
    $waktuKemarin = date('Y-m-d', strtotime('-1 month', strtotime($waktuSekarang)));
    return $waktuKemarin;
}

function waktuJatuhTempo($waktuSekarang, $durasi)
{
    $waktuJatuhTempo = date('Y-m-d', strtotime($durasi, strtotime($waktuSekarang)));
    return $waktuJatuhTempo;
}


function cekTahun($tanggal1, $tanggal2)
{
    $cekTahun1     = new DateTime($tanggal1);
    $tahun1        = $cekTahun1->format('Y');

    $cekTahun2     = new DateTime($tanggal2);
    $tahun2        = $cekTahun2->format('Y');

    $status = 'sama';
    if ($tahun1 != $tahun2) {
        $status = 'beda';
    }
    return $status;
}

function tampilTahun($tanggal)
{
    $cekTahun = new DateTime($tanggal);
    $tahun   = $cekTahun->format('Y');

    return $tahun;
}

function cekPeriodeTahun($tanggal)
{
    $cekTahun     = new DateTime($tanggal);
    $tahun        = $cekTahun->format('Y');
    $tanggalAwal  = $tahun . '-01-01';
    $tanggalAkhir = $tahun . '-12-31';

    $periodeTahun = array('tanggalAwal' => $tanggalAwal, 'tanggalAkhir' => $tanggalAkhir);
    return $periodeTahun;
}

function tampilBulanTahun($tanggal)
{
    $tanggalBaru  = strtotime($tanggal);
    $bulan = date('M', $tanggalBaru);
    $tahun = date('Y', $tanggalBaru);
    $periode = $bulan . ' ' . $tahun;
    return $periode;
}

function umur($tanggalLahir)
{
    $tglSekarang = date("Y-m-d");
    $lahir       = date_create($tanggalLahir);
    $sekarang    = date_create($tglSekarang);
    $jumlahHari  = date_diff($lahir, $sekarang);
    $tahun       = $jumlahHari->format("%y");
    $bulan       = $jumlahHari->format("%m");
    $hari        = $jumlahHari->format("%d");

    $arrayUmur   = array('umur' => $tahun, 'bulan' => $bulan, 'hari' => $hari);
    return $arrayUmur;
}

function ubahTanggalIndo($tanggal, $short = true)
{
    if (isDate($tanggal)) {
        $reversed = array_reverse(explode("-", $tanggal));
        $reversed[1] = namaBulan($reversed[1], $short);

        return join(' ', $reversed);
    } else {
        return '-';
    }
}

function isDate($value)
{
    if (!$value) {
        return false;
    } else {
        $date = date_parse($value);
        if ($date['error_count'] == 0 && $date['warning_count'] == 0) {
            return true;
        } else {
            return false;
        }
    }
}

function formatTanggal(?string $input, string $fmtFrom, string $fmtTo)
{
    if (is_null($input)) $input = date('Y-m-d');

    switch ($fmtFrom) {
        case 'Y-m-d':
            [$year, $month, $date] = explode('-', $input);
            break;
        case 'd/m/Y':
            [$date, $month, $year] = explode('/', $input);
            break;

        default:
            return false;
    }

    $year = trim($year);
    $month = trim($month);
    $date = trim($date);

    switch ($fmtTo) {
        case 'Y-m-d':
            return join('-', [$year, $month, $date]);
        case 'd/m/Y':
            return join('/', [$date, $month, $year]);

        default:
            return false;
    }
}
