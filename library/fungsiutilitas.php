<?php
function selected($id1, $id2)
{
    return $id1 === $id2 ? 'selected' : 'tidak sama';
}

function checked($id1, $id2)
{
    return $id1 === $id2 ? 'checked' : '';
}

function getNamaPegawai(\PDO $db, $idPegawai)
{
    $sql = $db->prepare('SELECT * from pegawai where idPegawai=?');
    $sql->execute([
        $idPegawai
    ]);
    $data = $sql->fetch();

    return $data;
}

function cekVariabel($var1, $var2)
{
    if ($var2 == NULL) {
        return $var1;
    } else {
        return $var2;
    }
}

function ubahToEng($word)
{
    if ($word == 'Laki-laki') {
        $word = 'Male';
    } else if ($word == 'Laki-Laki') {
        $word = 'Male';
    } else if ($word == 'Perempuan') {
        $word = 'Female';
    } else if ($word == 'Reaktif') {
        $word = 'Reactive';
    } else if ($word == 'Positif') {
        $word = 'Positive';
    } else if ($word == 'Negatif') {
        $word = 'Negative';
    }
    return $word;
}


function getFileData(array $data, array $htmlName)
{
    $output = [
        'file' => [],
        'view' => [],
        'button' => [],
    ];

    // INISIASI KEY YANG ADA DI DALAM VARIABLE "output" DENGAN NILAI ARRAY KOSONG
    foreach ($htmlName as $value) {
        $output['file'][$value] = [];
        $output['view'][$value] = '#';
        $output['button'][$value] = [];
    }
    // MEMASUKAN NILAI DATA BERDASARKAN KEY
    foreach ($data as $key => $value) {
        $status = array_search($value['htmlName'], $htmlName, true);

        if ($status !== false) {
            $output['file'][$value['htmlName']] = $value;
            $output['view'][$value['htmlName']] = FILE_PREVIEW_DIR . '/?param=' . encryptURLParam([
                'kodeFile' => $value['kodeFile'],
                'tokenCSRF' => $_SESSION['tokenCSRF'],
            ]);
        }
    }

    // MENENTUKAN STATUS BUTTON UPLOAD BERDASARKAN KONDISI TERTENTU

    foreach ($htmlName as $value) {
        if (count($output['file'][$value]) === 0) {
            $output['button'][$value] = 'btn btn-success';
        } else {
            $output['button'][$value] = 'btn btn-secondary';
        }
    }

    return $output;
}


function getFolder(string $fileURL)
{
    $fileName = basename($fileURL);
    $array_url = parse_url($fileURL);

    $path = $array_url['path'];
    $path = rtrim($path, $fileName);

    $array_path = explode('/', $path);

    $folder = $array_path[count($array_path) - 2];

    return $folder;
}

function multipleStatus(array $status)
{
    $showDump = false;

    $statusFinal = true;
    foreach ($status as $index => $value) {
        $statusFinal = $statusFinal && $value;
        if ($showDump === true) {
            var_dump('INDEX :' . $index . '( ' . $value . ' )');
        }
    }

    return $statusFinal;
}

function getMenuDirectory(string $BASE_URL_PHP, string $searchDir)
{
    $BASE_DIR = $BASE_URL_PHP . MAIN_DIR;
    $list = scandir($BASE_DIR);

    $GROUP = array_filter($list, function ($file) use ($BASE_DIR) {
        return is_dir($BASE_DIR . DIRECTORY_SEPARATOR . $file) && $file !== '.' && $file !== '..';
    });

    $expFileDir = explode(DIRECTORY_SEPARATOR, $searchDir);
    $targetDir = array_reduce($GROUP, function ($initial, $dir) use ($expFileDir) {
        // JALANKAN PENCARIAN APABILA NILAI $initial BERNILAI FALSE
        if ($initial) return $initial;
        // CARI GROUP DIRECTORY YANG TERDAPAT PADA $searchDir
        if (!in_array($dir, $expFileDir, true)) return false;
        // AMBIL DIRECTORY SETELAHNYA
        return $expFileDir[array_search($dir, $expFileDir, true) + 1];
    }, false);

    return $targetDir ? $targetDir : 'NOT FOUND';
}

function generateJSVersion()
{
    if ($_SERVER['HTTP_HOST'] === 'localhost') {
        return '?v=' . uniqid('dev');
    } else {
        return '';
    }
}

function sanitizeInput(array &$_VAR, array $exception = [], array $opt = [])
{
    foreach ($_VAR as $index => $value) {
        if (!in_array($index, $exception)) {
            if (is_array($_VAR[$index])) {
                sanitizeInput($_VAR[$index]);
            } else {
                $_VAR[$index] = htmlspecialchars(strip_tags($_VAR[$index]));
            }
        }
    }
}

function highlightKeywords(string $search, string $text)
{
    $matches = [];
    $escaped = preg_quote($search, '/');

    if (preg_match('/' . $escaped . '/i', $text, $matches)) {
        $output = $text;
        foreach ($matches as $match) {
            $output = preg_replace('/' . preg_quote($match, '/') . '/', '<u style="color:#3699ff!important; font-size:inherit;">' . $match . '</u>', $output);
        }
        return $output;
    } else {
        return $text;
    }
}

function stringSlug(string $text, int $offset = 50)
{
    return strlen($text) < 50 ? $text : substr_replace($text, '...', $offset);
}

function escapeFilename(string $filename)
{
    $output = preg_replace('/\//', '_', $filename);
    return $output;
}

function genUUIDv4($data = null)
{
    // Generate 16 bytes (128 bits) of random data or use the data passed into the function.
    $data = $data ?? random_bytes(16);
    assert(strlen($data) === 16);

    // Set version to 0100
    $data[6] = chr(ord($data[6]) & 0x0f | 0x40);
    // Set bits 6-7 to 10
    $data[8] = chr(ord($data[8]) & 0x3f | 0x80);

    // Output the 36 character UUID.
    return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
}
