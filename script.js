function kl_checkAll(ids) {

	for(var id in ids) {
		var d = document.getElementById(ids[id]);
		d.checked = true;
	}

}

function kl_uncheckAll(ids) {

	for(var id in ids) {
		var d = document.getElementById(ids[id]);
		d.checked = false;
	}

}
