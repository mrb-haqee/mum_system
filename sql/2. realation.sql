-- Active: 1725330860668@@127.0.0.1@3306@masterdb

USE ptmargautamamandiri;
ALTER TABLE `sub_account`
ADD FOREIGN KEY (`kodeAccount`) REFERENCES `account` (`kodeAccount`);

ALTER TABLE `pemasukan_pengeluaran_lain`
ADD FOREIGN KEY (`kodeSub`) REFERENCES `sub_account` (`kodeSub`);

-- ALTER TABLE `pemasukan_pengeluaran_lain` ADD FOREIGN KEY (`idRekening`) REFERENCES `bank` (`idBank`);

-- ALTER TABLE `pemasukan_pengeluaran_lain` ADD FOREIGN KEY (`idRekening`) REFERENCES `petty_cash` (`idPettyCash`);

ALTER TABLE `biaya`
ADD FOREIGN KEY (`kodeAccount`) REFERENCES `account` (`kodeAccount`);

ALTER TABLE `budgeting_project_tim`
ADD FOREIGN KEY (`kodeBudgetingProject`) REFERENCES `budgeting_project` (`kodeBudgetingProject`);
ALTER TABLE `budgeting_project_biaya`
ADD FOREIGN KEY (`kodeBudgetingProject`) REFERENCES `budgeting_project` (`kodeBudgetingProject`);
ALTER TABLE `budgeting_project_anggaran`
ADD FOREIGN KEY (`kodeBudgetingProject`) REFERENCES `budgeting_project` (`kodeBudgetingProject`);
