<?php

if(!defined('DOKU_INC')) die();
if(!defined('DOKU_PLUGIN')) define('DOKU_PLUGIN',DOKU_INC.'lib/plugins/');
require_once(DOKU_PLUGIN.'action.php');

class action_plugin_klausuren_infopage extends DokuWiki_Action_Plugin {

	private $isInfoPage = false;

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
		$controller->register_hook('ACTION_ACT_PREPROCESS', 'BEFORE', $this,
			'checkInfoPage');
		$controller->register_hook('PARSER_WIKITEXT_PREPROCESS', 'BEFORE', $this,
			'showInfoPage');
		$controller->register_hook('COMMON_PAGETPL_LOAD', 'BEFORE', $this,
			'createInfoPage');
	}


	function checkInfoPage(&$event, $param) {

		global $user, $INFO, $ID, $ACT;

		if($user->data['username'] != 'Tim Roes')
			return;

		if($INFO['exists']) 
			//return;
			$x = 0;

		$s = split(':', $ID);

		if($s[count($s)-1] != 'klausuren_info')
			return;

		$this->isInfoPage = true;

	}

	function showInfoPage(&$event, $param) {

		global $ACT;

		if($ACT != 'show' || !$this->isInfoPage) 
			return;

		$content = file_get_contents(DOKU_PLUGIN.'/klausuren/infopage.txt');
		$event->data = $content;

	}

	function createInfoPage(&$event, $param) {

		if(!$this->isInfoPage)
			return;

		$content = file_get_contents(DOKU_PLUGIN.'/klausuren/infopage_edit.txt');
		$event->data['tpl'] = $content;

	}

}

