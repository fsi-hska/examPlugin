<?php

class helper_plugin_klausuren_download extends Dokuwiki_Plugin {

	function getInfo(){
		return null;
	}

	function getMethods(){
		return null;
	}

	private function getDozentFromExamn($dozenten, $semester, $course="", $doctype="") {

		if ($dozenten==NULL) $dozenten=array();
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

	private function getAllDozenten($kurs, $course="", $doctype="klausuren") {

		$path = DOKU_INC."data/pages/".$this->getConf('unterlagenNS');
		if($course!="") $path .= '/'.$course;
		$path .= '/'.$kurs.'/klausuren_info.txt';

		// No infos: return null
		if(!file_exists($path))
			return null;

		$parsing = false;
		$file = file($path, FILE_IGNORE_NEW_LINES);
		$dozenten = array();

		$i = 1;

		foreach($file as $line) {

			// Skip line if we are outside [klausuren]
			if($line == "[$doctype]") {
				$parsing = true;
				continue;
			} elseif(!$parsing) {
				continue;
			} elseif($line == "[/$doctype]") {
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

	function getAllExams($kurs, $semester = 0, $course="", $doctype=""){
		$filepath =  DOKU_INC."data/media/".$this->getConf('unterlagenNS');
		$pagepath =  DOKU_INC."data/pages/".$this->getConf('unterlagenNS');
		if($course!="") {
			$filepath .= '/'.$course;
			$pagepath .= '/'.$course;
		}
		$filepath .= '/'.$kurs;
		$pagepath .= '/'.$kurs;
		if($doctype!="") {
			$filepath .= '/'.$doctype;
			$pagepath .= '/'.$doctype;
		}


		$result = array();
		if (is_dir($filepath)) {
			$files = scandir($filepath);
			foreach ($files as $file){
				if (preg_match('/^'.$kurs.'\_\d{4}(ws|ss)\_klausur(_loesung)?.pdf$/', $file)){
					$isCombi = (boolean)preg_match('/klausur_loesung.pdf$/',$file);
					$pdfSolution = str_replace('klausur', 'loesung', $file);
					$wikiSolution = str_replace('.pdf', '.txt', $pdfSolution);
					$sem = preg_replace('/^'.$kurs.'\_(\d{4})(ws|ss)\_klausur(_loesung)?.pdf$/', '$1$2', $file);
					if (!file_exists($filepath.'/'.$pdfSolution)){
						$pdfSolution = '';
					}
					$wikiSolutionExists = file_exists($pagepath.'/'.$wikiSolution);
					$wikiSolution = str_replace('.txt', '', $wikiSolution);
					$result[$sem] = array('klausur' => $file, 'pdfSolution' => $pdfSolution,
						'wikiSolution' => $wikiSolution, 'wikiSolutionExists' => $wikiSolutionExists,
						'isCombi' => $isCombi);
				}
			}
		}
		return $result;
	}

	function output(&$renderer, $data){
		global $ID;
		$help = plugin_load('helper','klausuren_helper');
		$klausuren = $this->getAllExams($data['lesson'], 0, $data['course'], $data['doctype']);

		if (sizeof($klausuren) > 0) {
			$lastExam = array_keys($klausuren);
			$lastExam = $lastExam[0];

			$lastDozent = array();
			$dozenten = $this->getAllDozenten($data['lesson'], $data['course'], $data['doctype']);

		    $renderer->doc .= '<form action="'.wl($ID).'" method="post">';
			$renderer->doc .= '<input type="hidden" name="lesson" value="'.$data['lesson'].'">';
			$renderer->doc .= '<input type="hidden" name="course" value="'.$data['course'].'">';
			$renderer->doc .= '<input type="hidden" name="doctype" value="'.$data['doctype'].'">';
	 		$renderer->doc .= '<table class="inline klausuren_download">';
	        $renderer->doc .= '<tbody>';

			$path =  $this->getConf('unterlagenNS');
			if($data['course']!="") $path .= '/'.$data['course'];
			$path .= "/". $data['lesson'] ;
			if($data['doctype']!="") $path .= '/'.$data['doctype'];
			$sem = $help->getCurrentSemester();
			while($sem >= $lastExam){
				$klausur = $klausuren[$sem];

				$dozent = $this->getDozentFromExamn($dozenten, $sem, $data['course'], $data['doctype']);

				if(empty($dozent) && $lastDozent !== null) {
					$link = $this->getConf('unterlagenNS');
					if($data['course']!="") $link .= '/'.$data['course'];
					$link .= '/'.$data['lesson'].'/klausuren_info';

					$renderer->doc .= '<tr><td colspan="4">Dozent unbekannt. Bitte <a href="'
						.wl($link).'">korrigieren</a>!</td></tr>';
					$lastDozent = null;
				} elseif($dozent != $lastDozent) {
					$renderer->doc .= '<tr><td colspan="4">Unterlagen von '.$dozent['name'].':</td></tr>';
					$lastDozent = $dozent;
				}


				if($klausur!= null){
					$jsKlausuren[] = '"'.$data['lesson'].'_'.$sem.'"';
					$renderer->doc .= '<tr>';
					$renderer->doc .= '<td><input type="checkbox" id="'.$data['lesson'].'_'.$sem.'" name="klausur_download[]" value="' . $sem . '" /></td>';
					if($klausur['isCombi']) {
						$renderer->doc .= '<td colspan="2">';
					} else {
						$renderer->doc .= '<td>';
					}
		      		$renderer->doc .= '<a href="' . wl('_media/' . $path . "/" . $klausur['klausur'] )
						. '" class="media mediafile mf_pdf">Klausur ';
					if($klausur['isCombi']) {
						$renderer->doc .= '+ Lösung ';
					}
					$renderer->doc .= $help->getNiceText($sem) . '</a></td>';
					if (!$klausur['isCombi'] && $klausur['pdfSolution'] != "") {
		      			$renderer->doc .= '<td><a href="' . wl('_media/' . $path . "/" .
							$klausur['pdfSolution'] ) . '" class="media mediafile mf_pdf">L&ouml;sung ' . '</a></td>';
					} elseif(!$klausur['isCombi']) {
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
			$renderer->doc .= '<input type="submit" name="button" class="button" value="Auswahl herunterladen (zip)"/>';
	        $renderer->doc .= '</form>';
		} else {
			$renderer->doc .= '<div class="noKlausuren">Leider stehen in diesem Fach noch keine Klausuren zur Verfügung.</div>';
		}
	}

	/**
	 * Compress the examns in the $lesson in the given $semesters (array) and
	 * return an zip object.
	 */
	function downloadAsZip($semesters, $lesson, $course="", $doctype="") {
		$filepath =  DOKU_INC."data/media/".$this->getConf('unterlagenNS').'/';
		$pagewebpath =  str_replace("/",":",$this->getConf('unterlagenNS')).':';
		$pagefilepath =  DOKU_INC."data/pages/".$this->getConf('unterlagenNS').'/';
		if($course!="") {
			$filepath .=  $course.'/';
			$pagewebpath .=  $course.':';
			$pagefilepath .=  $course.'/';
		}
		$filepath .=  $lesson.'/';
		$pagewebpath .=  $lesson.':';
		$pagefilepath .=  $lesson.'/';
		if($doctype!="") {
			$filepath .=  $doctype.'/';
			$pagewebpath .=  $doctype.':';
			$pagefilepath .=  $doctype.'/';
		}
		$helper =& plugin_load('helper', 'klausuren_helper');

		// Zip Libary einbinden
		require("zip.lib.php");
		require(dirname(__FILE__)."/../pdf/pdf.php");
		// neuse Zip Objekt erstellen
		$zipfile = new zipfile();

		foreach ($semesters as $semester) {
 			// Datei einlesen
			$filename = $lesson.'_'.$semester.'_klausur.pdf';
			if(!file_exists($filepath.$filename)) {
				$filename = $lesson.'_'.$semester.'_klausur_loesung.pdf';
			}
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
