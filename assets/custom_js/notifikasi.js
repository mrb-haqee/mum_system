function notifikasi(obj) {

	const flag = obj.flagNotif;
	let pesan = obj.pesan || null;

	if(flag == 'sukses'){
		if(!pesan){
			pesan = 'Proses data berhasil!';
		}

		toastr.success(pesan);
		$(".modal.show.utama").modal('hide');
	} 
	else if(flag == 'gagal'){
		if(!pesan){
			pesan = 'Proses data gagal!';
		}

		toastr.error(pesan);
	}
}
