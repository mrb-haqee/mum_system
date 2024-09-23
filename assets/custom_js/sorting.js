function sortingKolom(flagData, parameterOrder, fungsi_daftar, fungsi_cari){
  let parameterKirim;

  if(sortKolom == parameterOrder){
    parameterKirim = parameterOrder+(isASC ? ' ASC' : ' DESC');
    isASC = !isASC;
  }
  else{
    parameterKirim = parameterOrder+' ASC';
    isASC = false;
  }

  if(flagData == 'daftar'){
    fungsi_daftar(parameterKirim);
  }
  else{
    fungsi_cari(parameterKirim);
  }
  sortKolom = parameterOrder;
}