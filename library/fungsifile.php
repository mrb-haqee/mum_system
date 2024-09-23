<?php

/**
 * REQUIRED :
 * `$resource => [
 *      'noForm' => string,
 *      'htmlName' => string,
 *      'folder' => string
 * ]`
 * 
 * RETURN : 
 * `$return => [
 *      'status' => boolean,
 *      'pesan' => string,
 *      'more' => array
 * ]`
 */
function upload_file(array $resource, array $allowed_type = FILE_DEFAULT_ALLOWED_TYPE)
{
    $required = ['noForm', 'htmlName', 'folder'];
    $more = [
        'kodeFile' => '',
        'allowedType' => FILE_DEFAULT_ALLOWED_TYPE,
        'fileInputPreview' => base64_encode(""),
        'filePreview' => ""
    ];

    if (!(bool)array_reduce(array_keys($resource), function ($result, $key) use ($required) {
        return $result && (in_array($key, $required));
    }, true)) {
        return [
            'status' => false,
            'pesan' => 'Resource Tidak Lengkap',
            'more' => $more
        ];
    }

    ['htmlName' => $htmlName, 'noForm' => $noForm, 'folder' => $folder] = $resource;

    $idUser = dekripsi($_SESSION['idUser'], secretKey());

    $workingDir = ABS_PATH_FILE_UPLOAD_DIR . DIRECTORY_SEPARATOR . $folder . DIRECTORY_SEPARATOR;
    $previewDir = REL_PATH_FILE_UPLOAD_DIR . DIRECTORY_SEPARATOR . $folder . DIRECTORY_SEPARATOR;

    $cekFile = statementWrapper(
        DML_SELECT,
        'SELECT * FROM uploaded_file WHERE noForm = ? AND htmlName = ? AND folder = ?',
        [$noForm, $htmlName, $folder],
    );

    if ($cekFile) {

        $fileName = basename($cekFile['fileName']);
        $filePath = $workingDir . $cekFile['fileName'];

        if (file_exists($filePath)) {
            $status = unlink($filePath);
        }

        $statusDelete = statementWrapper(
            DML_DELETE,
            'DELETE FROM uploaded_file WHERE kodeFile = ?',
            [
                $cekFile['kodeFile']
            ]
        );
    }

    $file = $_FILES[$htmlName];

    if (!$file) {
        return [
            'status' => false,
            'pesan' => 'File Temp Tidak Ditemukan',
            'more' => $more
        ];
    }

    do {
        $kodeFile = md5(random_bytes(32));
        $cekGroup = statementWrapper(DML_SELECT, 'SELECT COUNT(*) as cek FROM uploaded_file WHERE kodeFile = ?', [$kodeFile])['cek'];
    } while (intval($cekGroup) > 0);

    $fileName = $kodeFile . '_' . basename($file['name']);
    $filePath = $workingDir . $fileName;

    $status = true;

    $fileType = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
    $fileSize = intval($file['size']);

    // Tidak Boleh Lebih Dari 1 MB
    if ($fileSize > FILE_MAX_SIZE) {
        return [
            'status' => false,
            'pesan' => 'Ukuran File Melebihi Batas Maksimum 1 MB',
            'more' => $more
        ];
    }
    // Ekstensi Tidak Boleh Selain PNG dan PDF
    if (!in_array($fileType, $allowed_type)) {
        return [
            'status' => false,
            'pesan' => 'Hanya Menerima File Berekstensi .jpg, .pdf, .jpeg, & .png',
            'more' => $more
        ];
    }

    $status = move_uploaded_file($file['tmp_name'], $filePath);

    if (!$status) {
        return [
            'status' => false,
            'pesan' => 'Proses Transfer File Gagal',
            'more' => $more
        ];
    }

    $status = statementWrapper(
        DML_INSERT,
        'INSERT INTO 
            uploaded_file 
        SET 
            noForm = ?,
            kodeFile = ?,
            fileName = ?,
            htmlName = ?,
            folder = ?,
            sizeFile = ?,
            ekstensi = ?,
            idUserInput = ?',
        [
            $noForm,
            $kodeFile,
            $fileName,
            $htmlName,
            $folder,
            $fileSize,
            $fileType,
            $idUser
        ]
    );

    if ($status) {
        $pesan = 'Proses Record Transfer File Telah Berhasil';
    } else {
        $pesan = 'Proses Record Transfer File Gagal';
    }

    $more = [
        'kodeFile' => $kodeFile,
        'fileInputPreview' => base64_encode("{$previewDir}/{$fileName}"),
        'filePreview' => FILE_PREVIEW_DIR . '/?param=' . rawurlencode(enkripsi(http_build_query([
            'kodeFile' => $kodeFile,
            'tokenCSRF' => $_SESSION['tokenCSRF'],
        ]), secretKey())),
        'allowedType' => FILE_DEFAULT_ALLOWED_TYPE
    ];

    return compact('status', 'pesan', 'more');
}

/**
 * REQUIRED :
 * `$resource => [
 *      'kodeFile' => string,
 *      'folder' => 'string,
 * ]`
 * 
 * RETURN : 
 * `$return => [
 *      'status' => boolean,
 *      'pesan' => string,
 *      'more' => array
 * ]`
 */

function delete_file(array $resource)
{
    $required = ['kodeFile', 'folder'];
    $more = [
        'kodeFile' => '',
        'allowedType' => FILE_DEFAULT_ALLOWED_TYPE,
        'fileInputPreview' => base64_encode(""),
        'filePreview' => ""
    ];

    if (!(bool)array_reduce(array_keys($resource), function ($result, $key) use ($required) {
        return $result && (in_array($key, $required));
    }, true)) {
        return [
            'status' => false,
            'pesan' => 'Resource Tidak Lengkap',
            'more' => $more
        ];
    }

    ['kodeFile' => $kodeFile, 'folder' => $folder] = $resource;

    $cekFile = statementWrapper(
        DML_SELECT,
        'SELECT * FROM uploaded_file WHERE kodeFile = ?',
        [$kodeFile]
    );

    if (!$cekFile) {
        return [
            'status' => false,
            'pesan' => 'Record File Tidak Ditemukan',
            'more' => $more
        ];
    }

    $filePath = ABS_PATH_FILE_UPLOAD_DIR . DIRECTORY_SEPARATOR . $folder . DIRECTORY_SEPARATOR . $cekFile['fileName'];

    if (file_exists($filePath)) {
        $status = unlink($filePath);
    }

    $status = statementWrapper(
        DML_DELETE,
        'DELETE FROM uploaded_file WHERE kodeFile = ?',
        [
            $cekFile['kodeFile']
        ]
    );

    if ($status) {
        $pesan = 'File Berhasil Dihapus';
    } else {
        $pesan = 'File Tidak Berhasil Dihapus';
    }

    $more = [
        'kodeFile' => $kodeFile,
        'allowedType' => FILE_DEFAULT_ALLOWED_TYPE,
        'fileInputPreview' => base64_encode(""),
        'filePreview' => base64_encode("")
    ];

    return compact('status', 'pesan', 'more');
}

function require_js_tags(string $directory)
{
    $directory = in_array($directory[-1], ['\\', '/']) ? $directory : $directory . DIRECTORY_SEPARATOR;
    $files = scandir($directory);

    if (!$files) return '';

    $dir_by_ds = preg_replace('/(\/)|(\\\)/', DIRECTORY_SEPARATOR, $directory);
    $base_by_ds = preg_replace('/(\/)|(\\\)/', '\\\\' . DIRECTORY_SEPARATOR, BASE_URL_HTML);

    $rel_path = preg_replace('/\S+(?=' . $base_by_ds . ')/', '', $dir_by_ds);

    $filtered = array_filter($files, function ($file) use ($directory) {
        return is_file($directory . DIRECTORY_SEPARATOR . $file) && $file !== '.' && $file !== '..';
    });

    $tags = array_map(function ($file) use ($directory, $rel_path) {
        return '<script src="' . $rel_path . $file . '?v=' . hash_file('crc32', $directory . DIRECTORY_SEPARATOR . $file) . '"></script>';
    }, $filtered);

    return join('', $tags);
}
