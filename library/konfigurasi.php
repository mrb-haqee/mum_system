<?php

// LAMBDA FUNCTION FOR CONCATING CONSTANT
$constant = function (string $name) {
    return constant($name) ?? '';
};

// TIME ZONE CONFIGURATION
date_default_timezone_set("Asia/Singapore");

// DATABASE DML CONSTANT
define('DML_SELECT', 1);
define('DML_SELECT_ALL', 2);
define('DML_INSERT', 3);
define('DML_UPDATE', 4);
define('DML_DELETE', 5);


// DIRECTORY CONSTANT CONFIGURATION
define('MAIN_DIR', '/system');
define('LIBRARY_DIR', '/library');
define('ASSETS_DIR', '/assets');

define('BASE_URL_HTML', '/mum_system');
define('BASE_URL_PHP', $_SERVER['DOCUMENT_ROOT'] . '/mum_system');

// PAGE CONSTANT CONFIGURATION
define('PAGE_TITLE', 'Master Daily System');
define('NUM_GEN_CODE', 'MUM');

// FILE TRANSFER CONFIGURATION
define('DEFAULT_FILE_UPLOAD_DIR', 'assets/uploaded_file');
define('REL_PATH_FILE_UPLOAD_DIR', BASE_URL_HTML . ASSETS_DIR . '/uploaded_file');
define('ABS_PATH_FILE_UPLOAD_DIR', BASE_URL_PHP . ASSETS_DIR . '/uploaded_file');
define('FILE_MAX_SIZE', 1048576); // ~ 1 MB
define('FILE_DEFAULT_ALLOWED_TYPE', ['png', 'jpg', 'pdf', 'jpeg']);
define('FILE_PREVIEW_DIR', BASE_URL_HTML . MAIN_DIR . '/preview');


// DATE FORMAT CONSTANT
define('DATABASE_PHP_DATE_FORMAT', 'Y-m-d');
define('INPUT_PHP_DATE_FORMAT', 'd/m/Y');

define('DATABASE_JS_DATE_FORMAT', 'YYYY-MM-DD');
define('INPUT_JS_DATE_FORMAT', 'DD/MM/YYYY');

// CURRENCY CONSTANT
define('CURRENCY_DEFAULT', 'IDR');
define('EXCHANGE_CACHE', true);
define('EXCHANGE_CACHE_REFRESH_TIME', '+2 minutes');
define('EXCHANGE_CACHE_DIR', BASE_URL_PHP . LIBRARY_DIR . '/exchange_cache/');

// AUTHORIZATION
define('VOID_AUTH_HASH', password_hash('void#2023', PASSWORD_DEFAULT));
define('USER_AUTHENTICATION_COOKIE', '_tmb_ua_');
define('MAX_LOGGED_USER', 50);
define('MAX_LOGIN_ATTEMPT', 5);
define('RESET_ATTEMPT_INTERVAL', 5); // IN MINUTES
define('MAX_PARAMS_STATE_DATA', 20);

define('CREDENTIAL_1', '$2y$10$MciIBvpDl3vSKCk8VPPdH.qI9xUNyxplHWSJsPAD.WXkpG90HjWje');
define('CREDENTIAL_2', '$2y$10$xlNPfdYPCwSz2jqDUGUhru8ehUcXGiJ4ad05TiB9hczT8FggayrtO');
define('CREDENTIAL_3', 'JSyNDIewKesPRZ57i04WHSM=');

//SECURE EVAL PAYLOAD FOR CREDENTIAL
define('CREDENTIAL_4', 'z87rSI8rMMZNrXRljkgYrpZr2ICjYeGDSlsBVYi1CtGKgyzyIVmVm/fgcEkqdY9DdxhXjcn4bpUH/NiZjyS8oaf9v+Rw947k5UKtPV5wGq0aexm5dVLfLt6J0f4Y9nF8L9xyfEmxMJfH+2lEW3UxtKjRe79d2W/uizoJ2XWc3+h4pmKMX9KoO04GLt+0VPfWBF4StzsnUBKFkDu2SKjNVVaQ/9QJwZ4x8QV7LkwceewnBw7sCQIxFr7FBlr+NvZm9vjOknyjaOWQXtFjXbKWSsOmqv9wcYSncFzDyHAdAU0Zl3PgXOvjvBpKmxaX4mIR8ziIPLLgCa9YOS7RsxQJmOCNLEfkTzK8PZupDEy+W7LsJrd7Bw71wCN8yZRCbiVID3yKIEtQpPbk+se29I14qawQhnGRBYgJJp9cRv5GzTuZzwWSNaism4kKKTpl4C26L5Hp5gn71UVl/X/0p+gB8SaHrtZnFi922KGSFnER4/bhZIPC5DFqhuevfuZKsNvk/ycwgQ==');

//LIBRARY CREDENTIAL
define('FREE_CURRENCY_API_KEY', 'fca_live_K0amq0L48bzbD3M3egjJ2S898apPxNj9JYiskMrr');