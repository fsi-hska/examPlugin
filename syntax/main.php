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
		$this->Lexer->addSpecialPattern('\{\{klausuren>[A-Za-z0-9]+?\}\}',$mode,'plugin_klausuren_main');
	}

	/**
	 * Prepare matches for the renderer.
	 * Extract the lesson from the used string.
	 */
	function handle($match, $state, $pos, &$handler){

		// Grep for lesson
		$lesson = substr($match, 12, -2);
		return array('lesson' => $lesson);

	}

	/**
	 * Renders the tag to the list of examns and if priviledeg the upload form.
	 */
	function render($mode, &$renderer, $data) {
		if($mode != 'xhtml')
			return false;

		for($i=1;$i < $renderer->lastlevel;$i++) {
			//$renderer->doc .= '</div>';
			$renderer->section_close();
		}

		$renderer->doc .= '<h2>Klausuren</h2>';
		
		// Show upload form if user is allowed to upload here
		if(auth_quickaclcheck($this->getConf('unterlagenNS').'/'.$data['lesson'].':*') >= AUTH_UPLOAD) {
			$uphelper =& plugin_load('helper', 'klausuren_upload');
			$renderer->section_open(2);
			$uphelper->output(&$renderer, $data);
			$renderer->doc .= '</div>';
		}	
		
		// Shows the list of examns
		$downhelper =& plugin_load('helper','klausuren_download');
		$renderer->section_open(2);
		$downhelper->output(&$renderer, $data);

		return true;

	}

}

//Setup VIM: ex: et ts=4 enc=utf-8 :
?>
