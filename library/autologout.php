<?php
// error_reporting(E_ALL);
// ini_set('display_errors','1');
include_once 'konfigurasi.php';
include_once 'konfigurasidatabase.php';
include_once 'fungsienkripsidekripsi.php';
include_once 'konfigurasikuncirahasia.php';
include_once 'fungsistatement.php';

session_start();

$idUser    = '';
$tokenCSRF = '';

extract($_SESSION);

//DESKRIPSI ID USER
$idUserAsli = dekripsi($idUser, secretKey());

//MENGECEK APAKAH ID USER YANG LOGIN ADA PADA DATABASE
$sqlCekUser = $db->prepare('SELECT * from user where idUser=?');
$sqlCekUser->execute([$idUserAsli]);
$dataCekUser = $sqlCekUser->fetch();

//KICK SAAT ID USER TIDAK ADA PADA DATABASE
if (!$dataCekUser) {
    header('location:' . BASE_URL_HTML . '/?flagNotif=gagal');
} else {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
?>
        <h3>ERROR 405. REQUEST METHOD "<?= $_SERVER['REQUEST_METHOD']; ?>" NOT ALLOWED</h3>
<?php
        http_response_code(405);
        exit;
    } else {
        $flag = $_POST['flag'];

        switch ($flag) {
            case 'verifikasi':

                if ($dataCekUser['autoLogOut'] === 'Aktif') {
                    $status = true;
                } else if ($dataCekUser['autoLogOut'] === 'Non Aktif') {
                    $status = false;
                } else {
                    $status = true;
                }

                echo json_encode([
                    'autoLogOut' => base64_encode(enkripsi($dataCekUser['autoLogOut'], secretKey()  )),
                    'status' => $status
                ]);
                break;
        }
    }
}
$db = null;
