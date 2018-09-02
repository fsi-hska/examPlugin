<?php

if(!defined('DOKU_INC')) die();
if(!defined('DOKU_PLUGIN')) define('DOKU_PLUGIN',DOKU_INC.'lib/plugins/');
require_once(DOKU_PLUGIN.'action.php');

class action_plugin_klausuren_upload extends DokuWiki_Action_Plugin {

	/**
	 * return some info
	 */
	function getInfo(){
		return array(
			'author' => 'Tim Roes',
			'email'  => 'mail@timroes.de',
			'date'   => '2011-07-30',
			'name'   => 'Upload Action',
			'desc'   => 'This component is repsonsible for the upload of files.',
			'url'    => 'http://www.hska.info'
		);
	}

	/**
	 * Register its handlers with the dokuwiki's event controller
	 */
	function register(Doku_Event_Handler $controller) {
		$controller->register_hook('ACTION_HEADERS_SEND', 'BEFORE', $this,
			'files_uploaded');
	}

	/**
	 * This method checks if the user sent some file to the server.
	 * If (s)he did so, valid all postet data, and move the uploaded file.
	 */
	function files_uploaded(&$event, $param) {

		$NS = $this->getConf('unterlagenNS').'/';
		if($_POST['course']!="") $NS .= $_POST['course'].'/';
		$NS .= $_POST['lesson'].'/';
		if($_POST['doctype']!="") $NS .= $_POST['doctype'].'/';
		$NS = cleanID($NS);

		if(!$_FILES['upload'])
			return;

		// check authes
		$AUTH = auth_quickaclcheck("$NS:*");
		if($AUTH < AUTH_UPLOAD) {
			msg("Keine Rechte die Datei hochzuladen.", -1);
			return;
		}

		// Check if post data is valid
		if(!in_array($_POST['type'], array('klausur','loesung','klausur_loesung'))
			|| !preg_match('/^\d{4}(ws|ss)$/', $_POST['semester'])
			|| !preg_match('/^(?:\w+)?$/', $_POST['course'])
			|| !preg_match('/^\w+$/', $_POST['lesson'])
			|| !preg_match('/^(?:\w+)?$/', $_POST['doctype'])) {
			//msg("Fehler im System. Dateiupload fehlgeschlagen.", -1);
			msg("Fehler im System. Dateiupload fehlgeschlagen.", -1);
			return;
		}

		// Check if file is empty
		if($_FILES['upload']['size'] <= 0) {
			msg("Du hast keine Datei ausgewählt. Bitte klicke zunächst auf \"Durchsuchen\" und wähle dann die Klausur oder Lösung aus. ", -1);
			return;
		}

		// Check if filetype is pdf
		if(mime_content_type($_FILES['upload']['tmp_name']) != 'application/pdf') {
			msg("Die Datei muss im PDF Format vorliegen. Falls du sie nicht konvertieren kannst, maile uns die Datei bitte.", -1);
			return;
		}

		$helper =& plugin_load('helper', 'klausuren_helper');
		$exists = $helper->getKlausurStatus($_POST['semester'], $_POST['lesson']);

		// If you try to upload a klausur when a klausur + lösung allready exists
		if($_POST['type'] == "klausur") {
			if($exists['klausur_loesung']) {
				// TODO could be improved to move the klausur_loesung to loesung and upload this klausur
				msg("Es ist bereits eine Klausur + Lösung hochgeladen.", -1);
				return;
			}
		}

		// Check if neither klausur nor loesung has been uploaded
		if($_POST['type'] == "klausur_loesung") {
			if($exists['klausur'] || $exists['loesung']) {
				msg("Es ist bereits eine Klausur und/oder Lösung hochgeladen.", -1);
				return;
			}
		}

		// Check if klausur exists if solution is uploaded
		if($_POST['type'] == "loesung") {
			if($exists['klausur_loesung']) {
				msg("Wir haben bereits eine Klausur + Lösung des entsprechenden Semesters.", -1);
				return;
			}
			if(!$exists['klausur']) {
				msg("Bitte zunächst die Klausur von dem entsprechenden Semester hochladen.", -1);
				return;
			}
		}

		// handle upload
		if($_FILES['upload']['tmp_name']){
			$_POST['mediaid'] = $_POST['lesson'].'_'.$_POST['semester'].'_'.$_POST['type'].'.pdf';
			$JUMPTO = media_upload($NS,$AUTH);
		    if($JUMPTO) {
				$NS = getNS($JUMPTO);
				$ID = $_POST['page'];
				$NS = getNS($ID);
			}
		}

	}

}

