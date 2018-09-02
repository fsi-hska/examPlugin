<?php

if(!defined('DOKU_INC')) die();
if(!defined('DOKU_PLUGIN')) define('DOKU_PLUGIN',DOKU_INC.'lib/plugins/');
require_once(DOKU_PLUGIN.'syntax.php');

class syntax_plugin_klausuren_main extends DokuWiki_Syntax_Plugin {

	function getInfo(){
		return array(
			'author' => 'Tim Roes',
			'email'  => 'mail@timroes.de',
			'date'   => '2011-07-30',
			'name'   => 'Syntax connector',
			'desc'   => 'This enables the {{klausuren>*}} syntax.',
			'url'    => 'http://www.hska.info',
		);
	}

	function getType(){
		return 'substition';
	}

	function getPType(){
		return 'block';
	}

	function getSort(){
		return 50;
	}

	function connectTo($mode) {
		$this->Lexer->addSpecialPattern('\{\{klausuren>(?:[\d\w]+\/)?\w+?(?:>\w+?)?\}\}',$mode,'plugin_klausuren_main');
	}

	/**
	 * Prepare matches for the renderer.
	 * Extract the lesson from the used string.
	 */
	function handle($match, $state, $pos, Doku_Handler $handler){

		// Grep for lesson
		preg_match('/\{\{klausuren>(?:([\w\d]+)\/)?(\w+?)(?:>(\w+?))?\}\}/',$match,$data);
		$lesson = $data[1];
		$course = $data[2];
		$doctype= isset($data[3])?isset($data[3]):'klausuren';
		return array('course' => $data[1], 'lesson' => $data[2], 'doctype' => $data[3]);
	}

	/**
	 * Renders the tag to the list of examns and if priviledeg the upload form.
	 */
	function render($mode, Doku_Renderer $renderer, $data) {
		if($mode != 'xhtml')
			return false;

		/*for($i=1;$i < $renderer->lastlevel;$i++) {
			//$renderer->doc .= '</div>';
			$renderer->section_close();
		}*/

		//$renderer->doc .= '<h2>Klausuren</h2>';

		// Show upload form if user is allowed to upload here
		if(auth_quickaclcheck(str_replace('/', ':', $this->getConf('unterlagenNS')).':'.$data['lesson'].':*') >= AUTH_UPLOAD) {
			$uphelper =& plugin_load('helper', 'klausuren_upload');
			$renderer->section_open(2);
			$uphelper->output($renderer, $data);
			$renderer->doc .= '</div>';
		}

		// Shows the list of examns
		$downhelper =& plugin_load('helper','klausuren_download');
		//$renderer->section_open(2);
		$downhelper->output($renderer, $data);

		return true;

	}

}

//Setup VIM: ex: et ts=4 enc=utf-8 :
?>
