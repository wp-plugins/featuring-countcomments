/*
toggle related fields
checkbox and array of fields
*/

function featuring_countcomments_toggle_related_fields(element, fields, checked) {

	if (element.checked==checked) {
		for (var i=0;i<fields.length;i++) {
			$('featuring_countcomments_'+fields[i]).disabled=null;
		}
	}

	else {
		for (var i=0;i<fields.length;i++) {
			$('featuring_countcomments_'+fields[i]).disabled='disabled';
		}
	}
}