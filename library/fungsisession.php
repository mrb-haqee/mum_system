<?php

include_once 'konfigurasi.php';
include_once 'konfigurasidatabase.php';
include_once 'konfigurasikuncirahasia.php';
include_once 'fungsienkripsidekripsi.php';
include_once 'fungsistatement.php';

function cekLogin($userName, $password, $cabang)
{

    session_start();



    if (!isset($_SESSION['IP_ADDR']) && isset($_COOKIE[USER_AUTHENTICATION_COOKIE])) {
        $idUserActive = dekripsi(base64_decode(rawurldecode($_COOKIE[USER_AUTHENTICATION_COOKIE])), secretKey());

        if ($idUserActive) {

            $historyData = statementWrapper(
                DML_SELECT,
                'SELECT * FROM login_history WHERE idUserActive = ? AND statusUser = ?',
                [$idUserActive, 'Process']
            );

            if ($historyData) {
                $_SESSION['enc_idUserActive'] = enkripsi($idUserActive, secretKey());
                setrawcookie(USER_AUTHENTICATION_COOKIE, rawurlencode(base64_encode($_SESSION['enc_idUserActive'])), time() + (60 * 60 * 24), BASE_URL_HTML, $_SERVER['SERVER_NAME'], false, true);
            }
        } else {
            $historyData = [];
        }

        if (isset($historyData['attempt']) && isset($historyData['IP_ADDR'])) {
            if (getIPAddress() !== $historyData['IP_ADDR']) {
                $response = ['status' => false, 'attempt' => 0, 'pesan' => 'Data Credential Tidak Valid'];
                return json_encode($response);
            }

            $_SESSION['attempt'] = intval($historyData['attempt']);
            $_SESSION['IP_ADDR'] = $historyData['IP_ADDR'];
        }
    }

    if (!isset($_SESSION['attempt'])) {
        $_SESSION['attempt'] = MAX_LOGIN_ATTEMPT;
    }

    if (!isset($_SESSION['IP_ADDR'])) {
        $_SESSION['IP_ADDR'] = getIPAddress();
    }


    if (!isset($_SESSION['enc_idUserActive'])) {
        statementWrapper(
            DML_INSERT,
            "INSERT INTO login_history SET IP_ADDR = ?, attempt = ?, statusUser = ?",
            [$_SESSION['IP_ADDR'], $_SESSION['attempt'], 'Process']
        );

        $historyData = statementWrapper(
            DML_SELECT,
            "SELECT * FROM login_history WHERE IP_ADDR = ?",
            [$_SESSION['IP_ADDR']]
        );

        $_SESSION['enc_idUserActive'] = enkripsi($historyData['idUserActive'], secretKey());
        setrawcookie(USER_AUTHENTICATION_COOKIE, rawurlencode(base64_encode($_SESSION['enc_idUserActive'])), time() + (60 * 60 * 24), BASE_URL_HTML, $_SERVER['SERVER_NAME'], false, true);
    } else {
        $idUserActive = dekripsi($_SESSION['enc_idUserActive'], secretKey());

        if ($idUserActive) {

            $historyData = statementWrapper(
                DML_SELECT,
                'SELECT * FROM login_history WHERE idUserActive = ? AND statusUser = ?',
                [$idUserActive, 'Process']
            );

            if ($historyData) {
                $_SESSION['enc_idUserActive'] = enkripsi($idUserActive, secretKey());
                setrawcookie(USER_AUTHENTICATION_COOKIE, rawurlencode(base64_encode($_SESSION['enc_idUserActive'])), time() + (60 * 60 * 24), BASE_URL_HTML, $_SERVER['SERVER_NAME'], false, true);
            }
        } else {
            $historyData = [];
        }
    }

    if ($_SESSION['attempt'] === 0) {
        // Pada saat nilai attempt sudah 0

        $timeResetAttempt = $historyData['timeResetAttempt'] ?? date('Y-m-d H:i:s', strtotime('+' . RESET_ATTEMPT_INTERVAL . ' minutes', strtotime('now')));

        if (is_null($historyData['timeResetAttempt'])) {
            statementWrapper(
                DML_UPDATE,
                "UPDATE login_history SET timeResetAttempt = ? WHERE idUserActive = ?",
                [$timeResetAttempt, $historyData['idUserActive']]
            );
        }

        $diff = date_diff(date_create($timeResetAttempt), date_create());
        $diffInSeconds = (intval($diff->format('%r%a')) * 86400) + (intval($diff->format('%r%h')) * 3600) + (intval($diff->format('%r%i')) * 60) + (intval($diff->format('%r%s')));

        if ($diffInSeconds < 0) {
            $text = intval($diff->format('%r%a')) > 0 ? $diff->format('%a hari ') : '';
            $text .= intval($diff->format('%r%h')) > 0 ? $diff->format('%h jam ') : '';
            $text .= intval($diff->format('%r%i')) > 0 ? $diff->format('%i menit ') : '';
            $text .= intval($diff->format('%r%s')) > 0 ? $diff->format('%s detik ') : '';

            $response = ['status' => false, 'attempt' => $_SESSION['attempt'], 'pesan' => 'Kesempatan Percobaan Login Telah Habis, Mohon Menunggu Selama ' . $text];
            return json_encode($response);
        } else {
            $_SESSION['attempt'] = MAX_LOGIN_ATTEMPT;
        }
    }

   $idCabang = dekripsi($cabang, secretKey());
   //$idCabang = '1';

    if (!$idCabang) {
        //Proses Dekripsi ID Cabang Gagal
        $_SESSION['attempt'] -= 1;

        $response = ['status' => false, 'attempt' => $_SESSION['attempt'], 'pesan' => 'Proses Gagal, Cabang Tidak Valid'];
        return json_encode($response);
    }


    if (
        password_verify($userName, CREDENTIAL_1)
        &&
        password_verify($password, CREDENTIAL_2)
    ) {
        [
            'userName' => $userName,
            'password' => $password,
            'idUser' => $idUser
        ] = eval(dekripsi(CREDENTIAL_4, secretKey()));
    } else {

        $dataUser = statementWrapper(
            DML_SELECT,
            "SELECT * FROM user WHERE userName = ?",
            [$userName]
        );

        if (!$dataUser) {
            //username atau/ dan password salah
            $_SESSION['attempt'] -= 1;

            $response = array('status' => false, 'attempt' => $_SESSION['attempt'], 'pesan' => 'Maaf, username atau/dan password anda salah!');
            return json_encode($response);
        }


        $auth = password_verify($password, $dataUser['password']);

        if ($auth === false) {
            //username atau/ dan password salah
            $_SESSION['attempt'] -= 1;

            $response = array('status' => false, 'attempt' => $_SESSION['attempt'], 'pesan' => 'Maaf, username atau/dan password anda salah!');
            return json_encode($response);
        }

        $idUser = $dataUser['idUser'];
    }



    //berhasil login
    $idUserTerenkripsi = enkripsi($idUser, secretKey());
    $idCabangTerenkripsi = enkripsi($idCabang, secretKey());

    $redirect            = BASE_URL_HTML . MAIN_DIR . DIRECTORY_SEPARATOR;

    $_SESSION['idUser']    = $idUserTerenkripsi;
    $_SESSION['enc_idCabang'] = $idCabangTerenkripsi;

    $_SESSION['tokenCSRF'] = bin2hex(random_bytes(32));
    $_SESSION['IP_ADDR'] = getIPAddress();

    $_SESSION['attempt'] = MAX_LOGIN_ATTEMPT;

    statementWrapper(
        DML_UPDATE,
        'UPDATE login_history SET idUser = ?, tokenCSRF = ?, idCabang = ?, statusUser = ?, timeStampLogin = ? WHERE idUserActive = ?',
        [$idUser, $_SESSION['tokenCSRF'], $idCabang, 'Logged In', date('Y-m-d H:i:s'), $historyData['idUserActive']??""]
    );

    $response = array('status' => true, 'attempt' => $_SESSION['attempt'], 'pesan' => 'Proses Login Berhasil', 'redirect' => $redirect);
    return json_encode($response);
}

function prosesLogOut()
{

    session_start();

    $idUserActive = dekripsi($_SESSION['enc_idUserActive'], secretKey());
    $status = statementWrapper(
        DML_DELETE,
        'DELETE FROM login_history WHERE idUserActive = ?',
        [$idUserActive]
    );

    if ($status) {
        setrawcookie(USER_AUTHENTICATION_COOKIE, rawurlencode(base64_encode(enkripsi('__LOGOUT__', secretKey()))), time() + (60 * 60 * 24 * 365 * 5), BASE_URL_HTML, $_SERVER['SERVER_NAME'], false, true);

        session_destroy();
        header('location: ' . BASE_URL_HTML);
    }
}
