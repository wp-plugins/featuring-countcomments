/*
hide all option-page sections
*/

function featuring_countcomments_hide_sections() {
	for (var i=0;i<featuring_countcomments_sections.length;i++) {
			jQuery('#featuring_countcomments_'+featuring_countcomments_sections[i]+'_link').attr('className', '');
	jQuery('#featuring_countcomments_'+featuring_countcomments_sections[i]).css('display', 'none');
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

	jQuery('#featuring_countcomments_'+my_section).css('display', 'block');
	jQuery('#featuring_countcomments_'+my_section+'_link').attr('className', 'current');
	jQuery('#featuring_countcomments_section').attr('value', my_section);
}