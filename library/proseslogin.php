<?php

include_once 'fungsisession.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (isset($_POST['username']) && isset($_POST['password']) && isset($_POST['cabang'])) {

        $username = $_POST['username'];
        $password = $_POST['password'];
        $cabang = $_POST['cabang'];

        echo cekLogin($username, $password, $cabang);
    }
}
