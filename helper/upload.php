<?php

class helper_plugin_klausuren_upload extends Dokuwiki_Plugin {

	function getInfo(){
		return null;
	}

	function getMethods(){
		return null;
	}

	function output(&$renderer, $data){

		global $ID;

		$helper =& plugin_load('helper','klausuren_helper');

		$params = array();
		$params['id'] = 'klausuren_upload';
		$params['action'] = wl($ID);
		$params['method'] = 'post';
		$params['enctype'] = 'multipart/form-data';
		$params['class'] = 'klausuren_upload';

		$form = new Doku_Form($params);
		$form->StartFieldset('Klausuren/Lösungen hochladen');
		$form->addElement('<div>');
		$form->addElement(formSecurityToken());
		$form->addHidden('page', hsc($ID));
		$form->addHidden('lesson', $data['lesson']);
		$form->addHidden('course', $data['course']);
		$form->addHidden('doctype', $data['doctype']);
		$form->addElement(form_makeFileField('upload', "", 'upload_file', '',
		   	array('accept' => 'application/pdf')));
		$form->addElement('<br>');
		$form->addElement(form_makeListboxField('semester', $helper->getLastSemesters(0), '', ''));
		$form->addElement(form_makeListboxField('type',
				array('klausur' => 'Klausur', 'loesung' => 'Lösung', 'klausur_loesung' => 'Klausur + Lösung'),
			'', ''));
		$form->addElement(form_makeButton('submit', '', 'Hochladen'));
		$form->addElement('</div>');
		$form->endFieldset();
		$renderer->doc .= $form->getForm();

	}
}

?>

