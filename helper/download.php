<?php

class helper_plugin_klausuren_download extends Dokuwiki_Plugin {

	function getInfo(){
		return null;
	}

	function getMethods(){
		return null;
	}

	private function getDozentFromExamn($dozenten, $semester) {

		$ff = function($var) use ($semester) {
			return (strcmp($semester, $var['from']) >= 0
				&& strcmp($semester, $var['to']) <= 0);
		};

		$filter = array_filter($dozenten, $ff);

		if(count($filter) == 0) {
			return null;
		} elseif(count($filter) == 1) {
			return reset($filter);
		} else {
			// if more then one element was found look if one of it is specific
			$ff2 = function($var) {
				return $var['to'] == $var['from'];
			};

			$filter2 = array_filter($filter, $ff2);

			return (count($filter2) > 0) ? reset($filter2) : reset($filter[0]);
			
		}

	}

	private function getAllDozenten($kurs) {

		$path = DOKU_INC."data/pages/".$this->getConf('unterlagenNS').'/'.$kurs.'/klausuren_info.txt';

		// No infos: return null
		if(!file_exists($path))
			return null;

		$parsing = false;
		$file = file($path, FILE_IGNORE_NEW_LINES);
		$dozenten = array();
		
		$i = 1;

		foreach($file as $line) {

			// Skip line if we are outside [klausuren]
			if($line == "[klausuren]") {
				$parsing = true;
				continue;
			} elseif(!$parsing) {
				continue;
			} elseif($line == "[/klausuren]") {
				$parsing = false;
				break;
			}

			if(!preg_match('/^((?:\d{4}(?:ws|ss))|\.\.)(?:-((?:\d{4}(?:ws|ss))|\.\.))?:(.+)$/', $line, $matches)) {
				return $i;
			} else {
				$to = (empty($matches[2])) ? $matches[1] : $matches[2];
				$dozenten[] = array(
					'name' => $matches[3],
					'from' => $matches[1],
					'to' => ($to == '..') ? '~~' : $to
				);
			}

			$i++;

		}

		return $dozenten;

	}

	function getAllExams($kurs, $semester = 0){
		$filepath =  DOKU_INC."data/media/".$this->getConf('unterlagenNS').'/'.$kurs;
		$pagepath =  DOKU_INC."data/pages/".$this->getConf('unterlagenNS').'/'.$kurs;
		$result = array();
		$files = scandir($filepath);
		foreach ($files as $file){
			if (preg_match('/^'.$kurs.'\_\d{4}(ws|ss)\_klausur.pdf$/', $file)){
				$pdfSolution = str_replace('klausur', 'loesung', $file);
				$wikiSolution = str_replace('.pdf', '.txt', $pdfSolution);
				$sem = preg_replace('/^'.$kurs.'\_(\d{4})(ws|ss)\_klausur.pdf$/', '$1$2', $file);
				if (!file_exists($filepath.'/'.$pdfSolution)){
					$pdfSolution = '';
				}
				$wikiSolutionExists = file_exists($pagepath.'/'.$wikiSolution); 
				$wikiSolution = str_replace('.txt', '', $wikiSolution);
				$result[$sem] = array('klausur' => $file, 'pdfSolution' => $pdfSolution, 
						'wikiSolution' => $wikiSolution, 'wikiSolutionExists' => $wikiSolutionExists);
			}
		}
		return $result;
	}

	function output(&$renderer, $data){
		global $ID;
		$help = plugin_load('helper','klausuren_helper');
		$klausuren = $this->getAllExams($data['lesson']);

		if (sizeof($klausuren) > 0) {
			$lastExam = array_keys($klausuren);
			$lastExam = $lastExam[0];

			$lastDozent = array();
			$dozenten = $this->getAllDozenten($data['lesson']);

			//if($dozenten === null)
			//	$renderer->doc .= '<div class="dozentenInfos">Es liegen noch keine Infos zu Dozenten vor.</div>';
			//elseif(is_int($dozenten))
			//	$renderer->doc .= '<div class="error">Fehler in den <a href="'.wl($this->getConf('unterlagenNS').'/'.$data['lesson'].'/klausuren_info')
			//		.'">Klausurinfos</a> in Zeile '.$dozenten.'.</div>';

	        $renderer->doc .= '<form action="'.wl($ID).'" method="post">';
			$renderer->doc .= '<input type="hidden" name="lesson" value="'.$data['lesson'].'">';
	 		$renderer->doc .= '<table class="inline klausuren_download">';
	        $renderer->doc .= '<tbody>';

			$path =  $this->getConf('unterlagenNS')."/". $data['lesson'] ;
			$sem = $help->getCurrentSemester();
			while($sem >= $lastExam){
				$klausur = $klausuren[$sem];

				$dozent = $this->getDozentFromExamn($dozenten, $sem);

				if(empty($dozent) && $lastDozent !== null) {
					$renderer->doc .= '<tr><td colspan="4">Dozent unbekannt. Korrigieren:  <a href="'.wl($this->getConf('helppage')).'">Wie</a>? ';
					$renderer->doc .= '<a href="'.wl($this->getConf('unterlagenNS').'/'.$data['lesson'].'/klausuren_info').'">Hier</a>!</td></tr>';
					$lastDozent = null;
				} elseif($dozent != $lastDozent) {
					$renderer->doc .= '<tr><td colspan="4">Klausuren von '.$dozent['name'].':</td></tr>';
					$lastDozent = $dozent;
				}

				if($klausur!= null){
					$jsKlausuren[] = '"'.$data['lesson'].'_'.$sem.'"';
					$renderer->doc .= '<tr>';
					$renderer->doc .= '<td><input type="checkbox" id="'.$data['lesson'].'_'.$sem.'" name="klausur_download[]" value="' . $sem . '" /></td>';
		      		$renderer->doc .= '<td><a href="' . wl('_media/' . $path . "/" . $klausur['klausur'] ) 
						. '" class="media mediafile mf_pdf">Klausur '. $help->getNiceText($sem) . '</a></td>';
					if ($klausur['pdfSolution'] != "") {
		      			$renderer->doc .= '<td><a href="' . wl('_media/' . $path . "/" . 
							$klausur['pdfSolution'] ) . '" class="media mediafile mf_pdf">L&ouml;sung ' . '</a></td>';
					} else {
						$renderer->doc .= '<td></td>';
					}
					if ($klausur['wikiSolutionExists'] == 1) {
		      				$renderer->doc .= '<td><a href="' . wl('' . $path . "/" . $klausur['wikiSolution'] ) . '">Wiki-L&ouml;sung ' . '</a></td>';
					} else {
			      			$renderer->doc .= '<td><a href="' . wl('' . $path . "/" . $klausur['wikiSolution'] ) . '" class="wikilink2" title="Noch keine Wikilösung vorhanden.">Wiki-L&ouml;sung ' . '</a></td>';
					}
					$renderer->doc .= '</tr>';
				} else {
					if ($sem != $help->getCurrentSemester()){
						$renderer->doc .= '<tr>';
						$renderer->doc .= '<td><input type="checkbox" name="klausur" value="' . $data['lesson'] . '_' . $sem . '" disabled="disabled"/></td>';
				      	$renderer->doc .= '<td colspan="3">Klausur ' . $help->getNiceText($sem) . ' nicht vorhanden.</a></td>';
						$renderer->doc .= '</tr>';
					}
				}
				$sem = $help->getPrevSemester($sem); 
			}
			$renderer->doc .= '<tr>';
			$renderer->doc .= '<td><input type="checkbox" id="selectAll" name="selectAll" value="selectAll" onChange=\'if(this.checked) kl_checkAll(['.implode(',', $jsKlausuren).']); else kl_uncheckAll(['.implode(',', $jsKlausuren).']);\'/></td>';
	   		$renderer->doc .= '<td colspan="3"><label class="selectAll" for="selectAll">Alle ausw&auml;hlen</label></a></td>';
			$renderer->doc .= '</tr>';

			$renderer->doc .= '</tbody>';
			$renderer->doc .= '</table>';
			$renderer->doc .= '<input type="submit" name="button" class="button" value="Herunterladen (zip)"/>';
	        $renderer->doc .= '</form>';
		} else {
			$renderer->doc .= '<div class="noKlausuren">Leider stehen in diesem Fach noch keine Klausuren zur Verfügung.</div>';
		}
	}

	/**
	 * Compress the examns in the $lesson in the given $semesters (array) and
	 * return an zip object.
	 */
	function downloadAsZip($semesters, $lesson) {
		$filepath =  DOKU_INC."data/media/".$this->getConf('unterlagenNS').'/'.$lesson.'/';
		$pagewebpath =  str_replace("/",":",$this->getConf('unterlagenNS')) . ':'.$lesson.':';
		$pagefilepath =  DOKU_INC."data/pages/".$this->getConf('unterlagenNS') . '/'.$lesson.'/';

		$helper =& plugin_load('helper', 'klausuren_helper');

		// Zip Libary einbinden
		require("zip.lib.php");
		require(dirname(__FILE__)."/../pdf/pdf.php");
		// neuse Zip Objekt erstellen
		$zipfile = new zipfile();

		foreach ($semesters as $semester) {
 			// Datei einlesen
			$filename = $lesson.'_'.$semester.'_klausur.pdf';
			if(file_exists($filepath.$filename)) {

				// Add examn to zip
				$zipfile->addFile($this->addFile($filepath.$filename), $filename, filemtime($filepath.$filename));

				// Add pdf solution to zip
				$filename = $lesson.'_'.$semester.'_loesung.pdf';
				if(file_exists($filepath.$filename)) {
					$zipfile->addFile($this->addFile($filepath.$filename), $filename, filemtime($filepath.$filename));
				}

				// Convert wiki solution and add it to zip
				$filename = $lesson.'_'.$semester.'_loesung';
				if(file_exists($pagefilepath.$filename.'.txt')) {
					$zipfile->addFile(PdfExport::convert($pagewebpath.$filename, 'Klausur '.$helper->getNiceText($semester).' '.$lesson.' Wiki'), 
						$filename.'_wiki.pdf', filemtime($pagefilepath.$filename.'.txt'));
				}
			} else {
				return null;
			}
		}
		// Zip File zurueckgeben
		return $zipfile;

	}

	private function addFile($filename){

		$handle = fopen ($filepath.$filename, "r");
		$content = fread ($handle, filesize ($filepath.$filename));
		fclose ($handle);
		// Datei in Zipfile speichern
		return $content;

	}

}

?>
