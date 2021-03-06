<?php

if(!defined('DOKU_INC')) die();
if(!defined('DOKU_PLUGIN')) define('DOKU_PLUGIN',DOKU_INC.'lib/plugins/');
require_once(DOKU_PLUGIN.'action.php');

class action_plugin_klausuren_download extends DokuWiki_Action_Plugin {

	/**
	 * return some info
	 */
	function getInfo(){
		return array(
			'author' => 'Tim Roes',
			'email'  => 'mail@timroes.de',
			'date'   => '2011-07-30',
			'name'   => 'Download Helper',
			'desc'   => 'Responsible for the download of archives. Zips requested files and let the user download them.',
			'url'    => 'http://www.hska.info'
		);
	}

	/**
	 * Register its handlers with the dokuwiki's event controller
	 */
	function register(Doku_Event_Handler $controller) {
		$controller->register_hook('ACTION_HEADERS_SEND', 'BEFORE', $this,
			'files_download');
		$controller->register_hook('PARSER_CACHE_USE', 'BEFORE', $this,
			'handle_cache');
	}


	function handle_cache(&$event, $param) {

		$cache =& $event->data;
		// Always invalidate cache (dirty but works...)
		$cache->depends['purge'] = true;

	}

	/**
	 * The function checks if a user has requested download of files.
	 * If (s)he has so, zip the requested files and rewrite http headers,
	 * so the file will be downloaded.
	 */
	function files_download(&$event, $param) {
		$helper = plugin_load('helper', 'klausuren_download');
		if(empty($_POST) || empty($_POST['button']) || ($_POST['button'] != $helper->getDownloadButtonText())) {
			return;
		}

		if(empty($_POST['klausur_download']) ) {
			$_POST['klausur_download'] = array();
			$klausuren = $helper->getAllExams($_POST['lesson']);
			foreach ($klausuren as $key => $value) {
				array_push($_POST['klausur_download'], $key);
			}
		}



		$NS = $this->getConf('unterlagenNS').'/';
		if($_POST['course']!="") $NS .= $_POST['course'].'/';
		$NS .= $_POST['lesson'].'/';
		if($_POST['doctype']!="") $NS .= $_POST['doctype'].'/';
		$NS = cleanID($NS);

		// check authes
		$AUTH = auth_quickaclcheck("$NS:*");
		if($AUTH < AUTH_READ) {
			msg("Keine Rechte die Dateien herunterzuladen.", -1);
			return;
		}

		// Check if post data is valid
		$filter = function($var) { return !preg_match('/^\d{4}(ws|ss)$/', $var); };
		$filtered = array_filter($_POST['klausur_download'], $filter);
		if(!empty($filtered) || !preg_match('/^\w+$/', $_POST['lesson']) || !preg_match('/^(?:\w+)?$/', $_POST['course']) || !preg_match('/^(?:\w+)?$/', $_POST['doctype'])) {
			msg("Fehler im System. Dateidownload fehlgeschlagen.", -1);
			return;
		}

		$zip = $helper->downloadAsZip($_POST['klausur_download'], $_POST['lesson'], $_POST['course'], $_POST['doctype']);

		if($zip == null) {
			msg("Es traten Fehler beim Verpacken der Dateien auf.", -1);
			return;
		}

		ob_clean();
		header("HTTP/1.1 200 OK");
		header("Content-Type: application/zip");
		header('Content-Disposition: attachment; filename="klausuren_'.$_POST['lesson'].'.zip"');
		header('Cache-Control: no-cache, must-revalidate');
		header("Content-Transfer-Encoding: binary");

		echo $zip->file();
		die();

	}

}

