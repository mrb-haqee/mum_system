<?php

/**
 * included at `BASE_URL_PHP . MAIN_DIR . /index.php`
 */

function widget_uang_masuk()
{
    $tanggalAwal = date('Y-m-01');
    $tanggalAkhir = date('Y-m-') . cal_days_in_month(CAL_GREGORIAN, intval(date('m')), intval(date('Y')));

    $bulan = namaBulan(intval(date('m')));

    $idCabang = dekripsi($_SESSION['enc_idCabang'], secretKey());

    $totalSekarang = statementWrapper(
        DML_SELECT,
        "SELECT
            SUM(hasilKonversi) as total
        FROM
            pendaftaran_pembayaran
        WHERE
            (tglPembayaran BETWEEN ? AND ? ) 
        ",
        [$tanggalAwal, $tanggalAkhir]
    )['total'];

    

?>
    <div class="col-xl-3 mb-5" id="widget_diagnosis_tertinggi" data-token="<?= $_SESSION['tokenCSRF'] ?>">
        <!-- CARD -->
        <div class="card card-custom bgi-no-repeat card-stretch gutter-b bg-danger" style="background-position: right top; background-size: 30% auto; height:300px; background-image: url(<?= BASE_URL_HTML ?>/assets/media/svg/shapes/abstract-4.svg)">

            <!-- CARD BODY -->
            <div class="card-body">
                <span class="svg-icon svg-icon-white svg-icon-3x ml-n1">
                    <svg width="24px" height="24px" viewBox="0 0 24 24" version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink">
                        <!-- Generator: Sketch 50.2 (55047) - http://www.bohemiancoding.com/sketch -->
                        <g id="Stockholm-icons-/-Communication-/-Thumbtack" stroke="none" stroke-width="1" fill="none" fill-rule="evenodd">
                            <rect id="bound" x="0" y="0" width="24" height="24"></rect>
                            <path d="M11.6734943,8.3307728 L14.9993074,6.09979492 L14.1213255,5.22181303 C13.7308012,4.83128874 13.7308012,4.19812376 14.1213255,3.80759947 L15.535539,2.39338591 C15.9260633,2.00286161 16.5592283,2.00286161 16.9497526,2.39338591 L22.6066068,8.05024016 C22.9971311,8.44076445 22.9971311,9.07392943 22.6066068,9.46445372 L21.1923933,10.8786673 C20.801869,11.2691916 20.168704,11.2691916 19.7781797,10.8786673 L18.9002333,10.0007208 L16.6692373,13.3265608 C16.9264145,14.2523264 16.9984943,15.2320236 16.8664372,16.2092466 L16.4344698,19.4058049 C16.360509,19.9531149 15.8568695,20.3368403 15.3095595,20.2628795 C15.0925691,20.2335564 14.8912006,20.1338238 14.7363706,19.9789938 L5.02099894,10.2636221 C4.63047465,9.87309784 4.63047465,9.23993286 5.02099894,8.84940857 C5.17582897,8.69457854 5.37719743,8.59484594 5.59418783,8.56552292 L8.79074617,8.13355557 C9.76799113,8.00149544 10.7477104,8.0735815 11.6734943,8.3307728 Z" id="Combined-Shape" fill="#000000"></path>
                            <polygon id="Path-111" fill="#000000" opacity="0.3" transform="translate(7.050253, 17.949747) rotate(-315.000000) translate(-7.050253, -17.949747) " points="5.55025253 13.9497475 5.55025253 19.6640332 7.05025253 21.9497475 8.55025253 19.6640332 8.55025253 13.9497475"></polygon>
                        </g>
                    </svg>
                </span>
                <div class="text-inverse-primary font-weight-bolder font-size-h1 mb-2 mt-5">Rp <?= ubahToRp($totalSekarang); ?></div>
                <div class="font-weight-bold text-inverse-primary font-size-lg">Total Uang Masuk <strong class="text-uppercase font-weight-bolder">(<?= namaBulan(date('m'), false); ?> <?= date('Y'); ?>)</strong> </div>
                
            </div>
            <!-- END CARD BODY -->
        </div>
    </div>
<?php
}



?>