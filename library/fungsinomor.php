<?php
include_once 'fungsistatement.php';

function nomorAntrian(\PDO $db, $idCabang)
{
    $sql = $db->prepare(
        'SELECT 
            COUNT(nomorAntrian) + 1 as nomorAntrian 
        FROM 
            pasien_antrian 
        WHERE 
            tanggalPendaftaran = ?
            AND idCabang = ?
            AND statusAntrian = ?
            AND nomorAntrian IS NOT NULL
        '
    );

    $sql->execute([
        date('Y-m-d'),
        $idCabang,
        'Aktif'
    ]);

    $data = $sql->fetch();

    return $data;
}

function nomorUrut(\PDO $db, $jenisNomor, $idUser)
{
    $data = statementWrapper(
        DML_SELECT,
        'SELECT nomorUrut from nomor_urut where jenisNomor = ? AND idUser = ?',
        [$jenisNomor, $idUser]
    );

    if (!$data) {
        $status = insertNomorUrut($db, $jenisNomor, $idUser);
    }

    $format = formatNomorUrut($db, $jenisNomor, $idUser);
    return $format;
}

function nomorVoucher(\PDO $db, $kodeAgent)
{
    $data = statementWrapper(
        DML_SELECT,
        'SELECT nomorVoucher, kodeVoucher from nomor_voucher where kodeAgent = ?',
        [$kodeAgent]
    );

    $voucher = str_pad($data['nomorVoucher'], 5, '0', STR_PAD_LEFT);
    $nomorVoucher = $data['kodeVoucher'] . '-' .  $voucher;
    return $nomorVoucher;
}


function nomorInvoice(\PDO $db, $kodeAgent)
{
    $data = statementWrapper(
        DML_SELECT,
        'SELECT nomorVoucher from nomor_voucher where kodeAgent = ?',
        [$kodeAgent]
    );

    $voucher = str_pad($data['nomorVoucher'], 4, '0', STR_PAD_LEFT);
    $nomorVoucher = $voucher;
    return $nomorVoucher;
}

function formatNomorUrut(\PDO $db, $jenisNomor, $idUser)
{
    $data = statementWrapper(
        DML_SELECT,
        'SELECT nomorUrut from nomor_urut where jenisNomor = ? AND idUser = ?',
        [$jenisNomor, $idUser]
    );

    if ($data) {


        switch ($jenisNomor) {
            case 'rekam_medis':
                $counter = str_pad($data['nomorUrut'], 5, '0', STR_PAD_LEFT);
                $date = date('ym');

                $format = $idUser . '.' . $date . $counter;
                break;
            default:
                $counter = str_pad($data['nomorUrut'], 9, '0', STR_PAD_LEFT);
                $format = NUM_GEN_CODE . '/' . $jenisNomor . '/' . $idUser . '/' . $counter;
                break;
        }

        return $format;
    } else {
        $random = bin2hex(random_bytes(48));
        return NUM_GEN_CODE . "-RND-{$random}";
    }
}

function insertNomorUrut(\PDO $db, $jenisNomor, $idUser)
{
    $status = statementWrapper(
        DML_INSERT,
        'INSERT INTO
            nomor_urut
        SET
            nomorUrut = ?,
            jenisNomor = ?,
            idUser = ?',
        [1, $jenisNomor, $idUser]
    );

    if ($status) {
        return true;
    } else {
        return false;
    }
}

function nomorUrutGudang(\PDO $db, $jenisNomor, $idUser)
{
    $sql = $db->prepare('SELECT nomorUrut from nomor_urut where jenisNomor=?');
    $sql->execute([$jenisNomor]);
    $data = $sql->fetch();

    $noUrut = $data['nomorUrut'] . $jenisNomor . $idUser . '/' . date('Y');
    return $noUrut;
}

function updateNomorUrut(\PDO $db, $jenisNomor, $idUser = '')
{
    if ($idUser === '') {
        $idUser = dekripsi($_SESSION['idUser'], secretKey());
    }

    $sql = $db->prepare(
        'UPDATE 
            nomor_urut 
        SET 
            nomorUrut = nomorUrut + 1
        WHERE 
            jenisNomor = ?
            AND idUser = ?
        '
    );
    $hasil = $sql->execute([$jenisNomor, $idUser]);

    return $hasil;
}

function updateNomorVoucher(\PDO $db, $kodeAgent)
{

    $sql = $db->prepare(
        'UPDATE 
            nomor_voucher 
        SET 
            nomorVoucher = nomorVoucher + 1
        WHERE 
            kodeAgent = ?
        '
    );
    $hasil = $sql->execute([$kodeAgent]);

    return $hasil;
}

function backNomorVoucher(\PDO $db, $kodeAgent)
{

    $sql = $db->prepare(
        'UPDATE 
            nomor_voucher 
        SET 
            nomorVoucher = nomorVoucher - 1
        WHERE 
            kodeAgent = ?
        '
    );
    $hasil = $sql->execute([$kodeAgent]);

    return $hasil;
}

function updateNomorInvoice(\PDO $db, $kodeAgent)
{

    $sql = $db->prepare(
        'UPDATE 
            nomor_voucher 
        SET 
            nomorVoucher = nomorVoucher + 1
        WHERE 
            kodeAgent = ?
        '
    );
    $hasil = $sql->execute([$kodeAgent]);

    return $hasil;
}
