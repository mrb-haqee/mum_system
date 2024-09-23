-- SELECT
--     pemasukan_pengeluaran_lain.idPemasukanPengeluaranLain as id,
--     pemasukan_pengeluaran_lain.tanggal as tanggal,
--     pemasukan_pengeluaran_lain.keterangan as uraian,
--     0 as debet,
--     pemasukan_pengeluaran_lain.nominal as kredit,
--     'kurang' as jenis,
--     pemasukan_pengeluaran_lain.timeStamp as timestamp
-- FROM
--     pemasukan_pengeluaran_lain
--     INNER JOIN petty_cash ON pemasukan_pengeluaran_lain.idRekening = petty_cash.idPettyCash
-- WHERE  pemasukan_pengeluaran_lain.tipe = 'Pengeluaran Lain'
--     AND pemasukan_pengeluaran_lain.jenisRekening = 'Petty Cash'

-- SELECT
--                 *
--             FROM
--                 (
--                     (
--                         SELECT
--                             pemasukan_pengeluaran_lain.tanggal as tanggal,
--                             CONCAT('Pemasukan Lain / ', COALESCE(account.namaAccount, 'null'), ' / ',COALESCE(sub_account.namaSubAccount, 'null')) as uraian,
--                             pemasukan_pengeluaran_lain.nominal as debet,
--                             0 as kredit,
--                             'tambah' as jenis,
--                             pemasukan_pengeluaran_lain.keterangan,
--                             pemasukan_pengeluaran_lain.timeStamp as timeStampInput
--                         FROM
--                             pemasukan_pengeluaran_lain
--                             INNER JOIN sub_account ON pemasukan_pengeluaran_lain.kodeSub = sub_account.kodeSub
--                             LEFT JOIN account ON account.kodeAccount = sub_account.kodeAccount
--                         WHERE
--                             (pemasukan_pengeluaran_lain.tanggal BETWEEN ? AND ?)
--                             AND pemasukan_pengeluaran_lain.idBank = ?
--                             AND pemasukan_pengeluaran_lain.tipe = 'Pemasukan Lain'
--                             AND pemasukan_pengeluaran_lain.jenisRekening = 'Bank'
--                     )
--                     UNION ALL
--                     (
--                         SELECT
--                             pemasukan_pengeluaran_lain.tanggal as tanggal,
--                             CONCAT('Pemasukan Lain / ', COALESCE(account.namaAccount, 'null'), ' / ',COALESCE(sub_account.namaSubAccount, 'null')) as uraian,
--                             0 as debet,
--                             pemasukan_pengeluaran_lain.nominal as kredit,
--                             'kurang' as jenis,
--                             pemasukan_pengeluaran_lain.keterangan as keterangan,
--                             pemasukan_pengeluaran_lain.timeStamp as timeStampInput
--                         FROM
--                             pemasukan_pengeluaran_lain
--                             LEFT JOIN sub_account ON pemasukan_pengeluaran_lain.kodeSub = sub_account.kodeSub
--                             LEFT JOIN account ON account.kodeAccount = sub_account.kodeAccount
--                         WHERE
--                             (pemasukan_pengeluaran_lain.tanggal BETWEEN ? AND ?)
--                             AND pemasukan_pengeluaran_lain.idBank = ?
--                             AND pemasukan_pengeluaran_lain.tipe = 'Pengeluaran Lain'
--                             AND pemasukan_pengeluaran_lain.jenisRekening = 'Bank'
--                     )
--                 ) detail_tunai
--                 ORDER BY tanggal, timeStampInput

-- <th>VENDOR</th>
-- <th>Tanggal Awal Kontrak</th>
-- <th>Tanggal Akhir Kontrak</th>
-- <th>Nominal</th>
-- <th>Nama PIC</th>

-- SELECT data1.*, data2.nominal, (
--         data2.nominal - data3.totalBiaya
--     ) as sisa_anggaran, data4.progres
-- FROM (
--         SELECT bp.*, vendor.nama
--         FROM
--             budgeting_project as bp
--             INNER JOIN vendor ON bp.kodeVendor = vendor.kodeVendor
--         WHERE
--             bp.statusBudgetingProject = 'Aktif'
--     ) as data1
--     INNER JOIN (
--         SELECT bp.kodeBudgetingProject, bpa.nominal
--         FROM
--             budgeting_project as bp
--             INNER JOIN budgeting_project_anggaran as bpa ON bp.kodeBudgetingProject = bpa.kodeBudgetingProject
--         WHERE
--             bp.statusBudgetingProject = 'Aktif'
--     ) as data2 ON data1.kodeBudgetingProject = data2.kodeBudgetingProject
--     LEFT JOIN (
--         SELECT bp.kodeBudgetingProject, SUM(bpb.subTotal) as totalBiaya
--         FROM
--             budgeting_project as bp
--             INNER JOIN budgeting_project_biaya as bpb ON bp.kodeBudgetingProject = bpb.kodeBudgetingProject
--         WHERE
--             bp.statusBudgetingProject = 'Aktif'
--     ) as data3 ON data1.kodeBudgetingProject = data3.kodeBudgetingProject
--     LEFT JOIN (
--         SELECT bp.kodeBudgetingProject, MAX(bpp.`idBudgetingProjectProgres`) as progres
--         FROM
--             budgeting_project as bp
--             INNER JOIN budgeting_project_progres as bpp ON bp.kodeBudgetingProject = bpp.kodeBudgetingProject
--         WHERE
--             bp.statusBudgetingProject = 'Aktif'
--         GROUP BY bp.`kodeBudgetingProject`
--     ) as data4 ON data1.kodeBudgetingProject = data4.kodeBudgetingProject

-- SELECT data1.*, data2.nominal, (
--         data2.nominal - data3.totalBiaya
--     ) as sisa_anggaran, data4.progres, data4.fileName, data4.folder
-- FROM (
--         SELECT bp.*, vendor.nama
--         FROM
--             budgeting_project as bp
--             INNER JOIN vendor ON bp.kodeVendor = vendor.kodeVendor
--         WHERE
--             bp.statusBudgetingProject = 'Aktif'
--     ) as data1
--     INNER JOIN (
--         SELECT bpa.kodeBudgetingProject, bpa.nominal
--         FROM
--             budgeting_project_anggaran as bpa
--     ) as data2 ON data1.kodeBudgetingProject = data2.kodeBudgetingProject
--     LEFT JOIN (
--         SELECT bpb.kodeBudgetingProject, SUM(bpb.subTotal) as totalBiaya
--         FROM
--             budgeting_project_biaya as bpb
--     ) as data3 ON data1.kodeBudgetingProject = data3.kodeBudgetingProject
--     LEFT JOIN (
--         SELECT bpp1.kodeBudgetingProject, bpp1.progres, uploaded_file.fileName, uploaded_file.folder
--         FROM
--             budgeting_project_progres as bpp1
--             INNER JOIN (
--                 SELECT
--                     MAX(budgeting_project_progres.idBudgetingProjectProgres) as idMax
--                 FROM
--                     budgeting_project_progres
--                 GROUP BY
--                     budgeting_project_progres.kodeBudgetingProject
--             ) as bpp2 ON bpp1.idBudgetingProjectProgres=bpp2.idMax
--             INNER JOIN uploaded_file ON bpp1.kodeBudgetingProjectProgres=uploaded_file.noForm
--     ) as data4 ON data1.kodeBudgetingProject = data4.kodeBudgetingProject

-- SELECT bp.*, vendor.nama, bpa.nominal, budgeting_project_biaya.*
-- FROM
--     budgeting_project AS bp
--     INNER JOIN vendor ON bp.kodeVendor = vendor.kodeVendor
--     INNER JOIN budgeting_project_anggaran AS bpa ON bp.kodeBudgetingProject = bpa.kodeBudgetingProject
--     INNER JOIN budgeting_project_biaya ON bp.kodeBudgetingProject = budgeting_project_biaya.kodeBudgetingProject
-- WHERE
--     bp.statusBudgetingProject = 'Aktif'
--     AND bp.idBudgetingProject = 1;
-- SELECT
--     bp.*,
--     GROUP_CONCAT(CASE WHEN bpt.jabatan = 'Leader' THEN pegawai.namaPegawai END) AS Leader,
--     GROUP_CONCAT(CASE WHEN bpt.jabatan = 'Anggota' THEN pegawai.namaPegawai END) AS Anggota,
--     GROUP_CONCAT(CASE WHEN bpt.jabatan = 'Penanggung Jawab' THEN pegawai.namaPegawai END) AS PenanggungJawab
-- FROM
--     budgeting_project AS bp
--     INNER JOIN budgeting_project_tim as bpt ON bp.kodeBudgetingProject = bpt.kodeBudgetingProject
--     INNER JOIN pegawai ON bpt.kodePegawai = pegawai.kodePegawai
-- WHERE
--     bp.statusBudgetingProject = 'Aktif'
--     AND bpt.`statusBudgetingProjectTim`='Aktif'
--     AND bp.idBudgetingProject = 1
-- GROUP BY
--     bp.kodeBudgetingProject;

-- SELECT bpb.*, SUM(bpb.subTotal)
-- FROM
--     budgeting_project AS bp
--     INNER JOIN budgeting_project_biaya as bpb ON bp.`kodeBudgetingProject` = bpb.`kodeBudgetingProject`
-- WHERE
--     bp.statusBudgetingProject = 'Aktif'
--     AND bp.idBudgetingProject = 1;
SELECT budgeting_project.`namaBudgetingProject`, budgeting_project_progres.*, uploaded_file.folder, uploaded_file.`fileName`
FROM
    budgeting_project
    INNER JOIN budgeting_project_progres ON budgeting_project.`kodeBudgetingProject` = budgeting_project_progres.`kodeBudgetingProject`
    INNER JOIN uploaded_file on uploaded_file.`noForm` = budgeting_project_progres.`kodeBudgetingProjectProgres`
WHERE
    budgeting_project_progres.kodeBudgetingProject = 'MUM/budgeting_project/1/000000001'