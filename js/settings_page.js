/*
hide all option-page sections
*/

function featuring_countcomments_hide_sections() {
	for (var i=0;i<featuring_countcomments_sections.length;i++) {
			$('featuring_countcomments_'+featuring_countcomments_sections[i]+'_link').className="";
	$('featuring_countcomments_'+featuring_countcomments_sections[i]).style.display="none";
	}
}

/*
opens admin-menu section
*/

function featuring_countcomments_open_section(section) {
	featuring_countcomments_hide_sections();

	var my_section='';

	if (section.length>0) {
		for (var i=0;i<featuring_countcomments_sections.length;i++) {
			if (featuring_countcomments_sections[i]==section) {
				my_section=section;
				break;
			}
		}
	}

	if (my_section.length===0)
		my_section=featuring_countcomments_sections[0];

	$('featuring_countcomments_'+my_section).style.display="block";
	$('featuring_countcomments_'+my_section+'_link').className="current";
	$('featuring_countcomments_section').value=my_section;
}