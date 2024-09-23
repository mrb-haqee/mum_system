<?php
function configDashboard()
{

    $config = [
        
        '__WIDGET_PENJUALAN__' => [
            'title' => 'Penjualan Bulanan',
            'abs_path' => BASE_URL_PHP . MAIN_DIR . '/dashboard/penjualan/widget.php',
            'rel_path' => BASE_URL_HTML . MAIN_DIR . '/dashboard/penjualan/widget.php',
            'js_path' => BASE_URL_HTML . MAIN_DIR . '/dashboard/penjualan/js/index.js',
            'function_init' => 'widget_penjualan'
        ],
        '__WIDGET_UANG_MASUK__' => [
            'title' => 'Uang Masuk',
            'abs_path' => BASE_URL_PHP . MAIN_DIR . '/dashboard/uang_masuk/widget.php',
            'rel_path' => BASE_URL_HTML . MAIN_DIR . '/dashboard/uang_masuk/widget.php',
            'js_path' => BASE_URL_HTML . MAIN_DIR . '/dashboard/uang_masuk/js/index.js',
            'function_init' => 'widget_uang_masuk'
        ],
    ];

    return $config;
}
