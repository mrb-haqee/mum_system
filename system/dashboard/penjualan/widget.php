<?php

/**
 * included at `BASE_URL_PHP . MAIN_DIR . /index.php`
 */

function widget_penjualan()
{
    $tanggalAwal = date('Y-m-01');
    $tanggalAkhir = date('Y-m-') . cal_days_in_month(CAL_GREGORIAN, intval(date('m')), intval(date('Y')));

    $dataHotel = statementWrapper(
        DML_SELECT,
        "SELECT
            SUM(subTotalJual) as totalHotel
        FROM
            pendaftaran_hotel INNER JOIN pendaftaran on pendaftaran_hotel.kodePendaftaran = pendaftaran.kodePendaftaran
        WHERE
            (tglPendaftaran BETWEEN ? AND ?)
            AND status = ?
        ",
        [$tanggalAwal, $tanggalAkhir, 'Confirm']
    );

    $dataActivity = statementWrapper(
        DML_SELECT,
        "SELECT
            SUM(subTotalJual) as totalActivity
        FROM
            pendaftaran_activity INNER JOIN pendaftaran on pendaftaran_activity.kodePendaftaran = pendaftaran.kodePendaftaran
        WHERE
            (tglPendaftaran BETWEEN ? AND ?)
            AND statusConfirm = ?
        ",
        [$tanggalAwal, $tanggalAkhir, 'Confirm']
    );

    $dataEkstra = statementWrapper(
        DML_SELECT,
        "SELECT
            SUM(subTotalEkstraJual) as totalEkstra
        FROM
            pendaftaran_hotel_ekstra INNER JOIN pendaftaran on pendaftaran_hotel_ekstra.kodePendaftaran = pendaftaran.kodePendaftaran
        WHERE
            (tglPendaftaran BETWEEN ? AND ?)
        ",
        [$tanggalAwal, $tanggalAkhir,]
    );

    $grandTotalConfirm = $dataHotel['totalHotel'] + $dataActivity['totalActivity'] + $dataEkstra['totalEkstra'];

?>
    <div class="col-xl-3 mb-5" id="widget_penjualan" data-token="<?= $_SESSION['tokenCSRF'] ?>">
        <div class="card card-custom bg-primary card-stretch gutter-b">
            <!--begin::Body-->
            <div class="card-body">
                <span class="svg-icon svg-icon-white svg-icon-3x ml-n1"><!--begin::Svg Icon | path:/metronic/theme/html/demo1/dist/assets/media/svg/icons/Media/Equalizer.svg--><svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" width="24px" height="24px" viewBox="0 0 24 24" version="1.1">
                        <g stroke="none" stroke-width="1" fill="none" fill-rule="evenodd">
                            <rect x="0" y="0" width="24" height="24"></rect>
                            <rect fill="#000000" opacity="0.3" x="13" y="4" width="3" height="16" rx="1.5"></rect>
                            <rect fill="#000000" x="8" y="9" width="3" height="11" rx="1.5"></rect>
                            <rect fill="#000000" x="18" y="11" width="3" height="9" rx="1.5"></rect>
                            <rect fill="#000000" x="3" y="13" width="3" height="7" rx="1.5"></rect>
                        </g>
                    </svg><!--end::Svg Icon--></span>
                <div class="text-inverse-primary font-weight-bolder font-size-h1 mb-2 mt-5">Rp <?= ubahToRp($grandTotalConfirm); ?></div>
                <div class="font-weight-bold text-inverse-primary font-size-lg">Total Penjualan <strong class="text-uppercase font-weight-bolder">(<?= namaBulan(date('m'), false); ?> <?= date('Y'); ?>)</strong> </div>
            </div>
            <!--end::Body-->
        </div>
    </div>
<?php
}



?>