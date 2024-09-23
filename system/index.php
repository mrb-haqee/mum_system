<?php
include_once '../library/konfigurasi.php';
include_once "{$constant('BASE_URL_PHP')}/library/konfigurasidatabase.php";
include_once "{$constant('BASE_URL_PHP')}/library/konfigurasikuncirahasia.php";
include_once "{$constant('BASE_URL_PHP')}/library/konfigurasidashboard.php";
include_once "{$constant('BASE_URL_PHP')}/library/fungsienkripsidekripsi.php";
include_once "{$constant('BASE_URL_PHP')}/library/fungsistatement.php";
include_once "{$constant('BASE_URL_PHP')}/library/fungsitanggal.php";
include_once "{$constant('BASE_URL_PHP')}/library/fungsirupiah.php";
include_once "{$constant('BASE_URL_PHP')}/library/fungsirupiah.php";
include_once "{$constant('BASE_URL_PHP')}/{$constant('MAIN_DIR')}/fungsinavigasi.php";

session_start();

$idUser    = '';
$tokenCSRF = '';

extract($_SESSION);

//DESKRIPSI ID USER
$idUserAsli = dekripsi($idUser, secretKey());

//MENGECEK APAKAH ID USER YANG LOGIN ADA PADA DATABASE
$sqlCekUser = $db->prepare('SELECT idUser from user where idUser=?');
$sqlCekUser->execute([$idUserAsli]);
$dataCekUser = $sqlCekUser->fetch();

//KICK SAAT ID USER TIDAK ADA PADA DATABASE
if (!$dataCekUser) {
	header('location:' . BASE_URL_HTML . '/?flagNotif=gagal');
} else {

?>

	<!DOCTYPE html>
	<html lang="en">
	<!-- HEAD -->

	<head>
		<meta charset="utf-8">
		<title><?= PAGE_TITLE; ?></title>
		<meta name="description" content="Page with empty content">
		<meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
		<meta name="author" content="TempatKita Software">

		<!-- CSS UTAMA SEMUA HALAMAN -->
		<link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Poppins:300,400,500,600,700">
		<link rel="stylesheet" href="<?= BASE_URL_HTML ?>/assets/plugins/global/plugins.bundle.css">
		<link rel="stylesheet" href="<?= BASE_URL_HTML ?>/assets/plugins/custom/prismjs/prismjs.bundle.css">
		<link rel="stylesheet" href="<?= BASE_URL_HTML ?>/assets/css/style.bundle.css">
		<link rel="stylesheet" href="<?= BASE_URL_HTML ?>/assets/css/themes/layout/header/base/light.css">
		<link rel="stylesheet" href="<?= BASE_URL_HTML ?>/assets/css/themes/layout/header/menu/light.css">
		<link rel="stylesheet" href="<?= BASE_URL_HTML ?>/assets/css/themes/layout/brand/dark.css">
		<link rel="stylesheet" href="<?= BASE_URL_HTML ?>/assets/css/themes/layout/aside/dark.css">
		<link rel="stylesheet" href="<?= BASE_URL_HTML ?>/assets/custom_css/loader.css">
		<!-- END CSS UTAMA SEMUA HALAMAN -->

		<?php
		icon(BASE_URL_HTML);
		?>
	</head>
	<!-- END HEAD -->

	<!-- LOADER -->
	<div class="overlay">
		<div class="overlay__inner">
			<div class="overlay__content">
				<span class="spinner"></span>
			</div>
		</div>
	</div>
	<!-- END LOADER -->

	<!-- BODY -->

	<body id="kt_body" class="header-fixed header-mobile-fixed subheader-enabled subheader-fixed aside-enabled aside-fixed aside-minimize-hoverable page-loading">

		<!-- HEADER MOBILE -->
		<?php
		headerMobile(BASE_URL_HTML);
		?>
		<!-- END MOBILE HEADER-->

		<div class="d-flex flex-column flex-root">
			<div class="d-flex flex-row flex-column-fluid page">

				<!-- ASIDE -->
				<?php
				aside(BASE_URL_HTML, $db, $idUserAsli, 0, 0);
				?>
				<!-- END ASIDE -->

				<div class="d-flex flex-column flex-row-fluid wrapper" id="kt_wrapper">
					<!-- HEADER -->
					<div id="kt_header" class="header header-fixed">
						<div class="container-fluid d-flex align-items-stretch justify-content-between">
							<div class="header-menu-wrapper header-menu-wrapper-left" id="kt_header_menu_wrapper">

								<!-- HEADER MENU -->
								<?php
								headerMenu(BASE_URL_HTML);
								?>
								<!-- END HEADER MENU-->

							</div>

							<!-- TOP BAR -->
							<div class="topbar">


								<!-- TOPBAR USER -->
								<?php
								topbarUser(BASE_URL_HTML, $db, $idUserAsli);
								?>
								<!-- END TOPBAR USER -->

							</div>
							<!-- END TOP BAR -->
						</div>
					</div>
					<!-- END HEADER -->

					<!-- CONTENT -->
					<div class="content d-flex flex-column flex-column-fluid" id="kt_content">
						<!-- SUB HEADER -->
						<div class="subheader py-2 py-lg-6 subheader-solid" id="kt_subheader">
							<div class="container-fluid d-flex align-items-center justify-content-between flex-wrap flex-sm-nowrap">

								<div class="d-flex align-items-center flex-wrap mr-1">
									<div class="d-flex align-items-baseline flex-wrap mr-5">

										<h5 class="text-dark font-weight-bold my-1 mr-5">Dashboard</h5>

										<ul class="breadcrumb breadcrumb-transparent breadcrumb-dot font-weight-bold p-0 my-2 font-size-sm">
											<li class="breadcrumb-item">
												<a href="" class="text-muted">General</a>
											</li>
											<li class="breadcrumb-item">
												<a href="" class="text-muted">Dashboard</a>
											</li>
										</ul>
									</div>
								</div>

							</div>
						</div>
						<!-- END SUB HEADER -->

						<!-- ENTRY -->
						<div class="container-fluid">
							<div style="width: 100%;">
								<div class="row" id="boxUserWidget">
									<?php
									
									
									$config = configDashboard();

									$aksesWidget = statementWrapper(
										DML_SELECT_ALL,
										'SELECT * FROM user_dashboard WHERE idUser = ? 
										ORDER BY idUserDashboard',
										[$idUserAsli]
									);

									$js = [];

									foreach ($aksesWidget as $index => $widget) {
										if (isset($config[$widget['widget']])) {
											['abs_path' => $abs_path, 'rel_path' => $rel_path, 'function_init' => $function_init, 'js_path' => $js_path] = $config[$widget['widget']];
											$js[] = '<script src="' . $js_path . '"></script>';

											if (file_exists($abs_path)) {
												include_once $abs_path;
												call_user_func($function_init);
											}
										}
									}

									?>
								</div>
							</div>
						</div>
						<!-- END ENTRY -->
					</div>
					<!-- END CONTENT -->

					<!-- FOOTER -->
					<?php
					footer(BASE_URL_HTML);
					?>
					<!-- END FOOTER-->

				</div>
			</div>
		</div>

		<!-- USER PANEL -->
		<?php
		userPanel(BASE_URL_HTML, $db, $idUserAsli);
		?>
		<!-- END USER PANEL -->

		<!-- JS UTAMA SEMUA HALAMAN -->
		<script src="<?= BASE_URL_HTML ?>/assets/custom_js/ktappsettings.js"></script>
		<script src="<?= BASE_URL_HTML ?>/assets/plugins/global/plugins.bundle.js"></script>
		<script src="<?= BASE_URL_HTML ?>/assets/plugins/custom/prismjs/prismjs.bundle.js"></script>
		<script src="<?= BASE_URL_HTML ?>/assets/js/scripts.bundle.js"></script>
		<!-- END JS UTAMA SEMUA HALAMAN -->
		<script src="<?= BASE_URL_HTML ?>/assets/custom_js/validasiform.js"></script>
		<script>
			function notifikasi(status, pesan) {
				if (status === true) {
					toastr.success(pesan);
				} else {
					toastr.error(pesan);
				}
			}
		</script>
		<?= join(' ', $js); ?>
	</body>
	<!-- END BODY -->

	</html>
<?php
}
