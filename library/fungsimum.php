<?php
function satuanBarang()
{
    return [
        'Kubik',
        'Meter',
        'Kilogram',
        'Ton',
        'Liter',
        'Lembar',
        'Batang',
        'Sak',
        'Roll',
        'Unit',
        'Set'
    ];
}
function jenisVendor()
{
    return [
        'Customer',
        'Supplier'
    ];
}

function getHargaDiskon($hargaNormal, $persentaseDiskon)
{
    return ($hargaNormal) * ($persentaseDiskon) / 100;
}

function getHargaPPN($hargaNormal, $persentaseDiskon, $persentasePpn)
{
    $hargaDiskon = getHargaDiskon($hargaNormal, $persentaseDiskon);
    return ($hargaNormal - $hargaDiskon) * $persentasePpn / 100;
}

function getHargaGrandTotal($subTotal, $persentaseDiskon, $persentasePpn)
{
    $hargaDiskon = getHargaDiskon($subTotal, $persentaseDiskon);
    $hargaPpn = getHargaPPN($subTotal, $persentaseDiskon, $persentasePpn);
    // return $subTotal - $hargaDiskon + $hargaPpn;
    return $subTotal-$hargaDiskon+$hargaPpn;
}
