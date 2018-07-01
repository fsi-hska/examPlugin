<?php

if(!defined('DOKU_INC')) die();
if(!defined('DOKU_PLUGIN')) define('DOKU_PLUGIN',DOKU_INC.'lib/plugins/');
require_once(DOKU_PLUGIN.'action.php');

class action_plugin_klausuren_infopage extends DokuWiki_Action_Plugin {

	/**
	 * return some info
	 */
	function getInfo(){
		return array(
			'author' => 'Tim Roes',
			'email'  => 'mail@timroes.de',
			'date'   => '2011-07-30',
			'name'   => 'Infopage Helper',
			'desc'   => 'Responsible for creating the infopages about the profs.',
			'url'    => 'http://www.hska.info'
		);
	}

	/**
	 * Register its handlers with the dokuwiki's event controller
	 */
	function register(&$controller) {
		$controller->register_hook('PARSER_WIKITEXT_PREPROCESS', 'BEFORE', $this,
			'showInfoPage');
		$controller->register_hook('COMMON_PAGETPL_LOAD', 'BEFORE', $this,
			'createInfoPage');
	}

	function _isInfoPage($id) {

		$s = explode(':', $id);

		return ($s[count($s)-1] == 'klausuren_info');

	}

	function showInfoPage(&$event, $param) {

		global $ACT, $ID;

		if($ACT != 'show' || !$this->_isInfoPage($ID))
			return;

		$content = file_get_contents(DOKU_PLUGIN.'/klausuren/infopage.txt');
		$exists = page_exists($ID);

		if($exists) {
			$event->data = $content
				."\n**Aktuell eingetragene Professoren**"
				."\n<code>".$event->data."\n</code>";
		} else {
			$event->data = $content;
		}

	}

	function createInfoPage(&$event, $param) {

		global $INFO, $ID;

		if(!$this->_isInfoPage($ID) || $INFO['exists'])
			return;

		$content = file_get_contents(DOKU_PLUGIN.'/klausuren/infopage_edit.txt');
		$event->data['tpl'] = $content;

	}

}

