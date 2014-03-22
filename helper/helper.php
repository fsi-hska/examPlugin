<?php

class helper_plugin_klausuren_helper extends Dokuwiki_Plugin {

	function getInfo(){
		return null;
	}

	function getMethods(){
		return null;
	}

	function getCurrentSemester(){
		$sem = array('ws','ss');

		if(date('m') <= 2) {
			$currentY = date('Y') - 1;
			$currentS = 0;
		} elseif (date('m') > 8) {
			$currentY = date('Y');
			$currentS = 0;
		} else {
			$currentY = date('Y');
			$currentS = 1;
		}
		return $currentY.$sem[$currentS];

	}


	function getLastSemesters($count = 10) {

		$sem = array('ws','ss');

		if(date('m') <= 2) {
			$currentY = date('Y') - 1;
			$currentS = 0;
		} elseif (date('m') > 8) {
			$currentY = date('Y');
			$currentS = 0;
		} else {
			$currentY = date('Y');
			$currentS = 1;
		}

		$ret = array();

		for($i = 0; $i < $count; $i++) {

			$s = $currentY.$sem[$currentS];
			$ret[$s] = $this->getNiceText($s);
			$currentS = ($currentS + 1) % 2;
			if($currentS == 0) {
				$currentY--;
			}

		}

		return $ret;

	}

	function getPrevSemester($oldSemester){
		$arr = str_split($oldSemester, 4);
		$arr[0] = ($arr[1] == "ws") ? $arr[0] : $arr[0] - 1;
		$arr[1] = ($arr[1] == "ws") ? "ss" : "ws";
		return $arr[0].$arr[1];
	}

	function getNiceText($sem){
		return strtoupper(preg_replace('/(\d{4})(ws|ss)/', '$2 $1', $sem));
	}

	function getKlausurStatus($semester, $lesson, $course="", $doctype=""){
        $filepath = DOKU_INC."data/media/".$this->getConf('unterlagenNS');
        $pagepath = DOKU_INC."data/pages/".$this->getConf('unterlagenNS');
		if($course!="") {
			$filepath .= '/'.$course;
			$pagepath .= '/'.$course;
		}
		$filepath .= '/'.$lesson;
		$pagepath .= '/'.$lesson;
		if($doctype!="") {
			$filepath .= '/'.$doctype;
			$pagepath .= '/'.$doctype;
		}

		$klausurFilename = $lesson."_".$semester."_klausur";
		$solutionFilename = $lesson."_".$semester."_loesung";
		$combiFilename = $lesson."_".$semester."_klausur_loesung";
		

		$isCombi = file_exists($filepath . '/' . $combiFilename . '.pdf');
		$klausurExist = file_exists($filepath . '/' . $klausurFilename . '.pdf');
		$pdfSolutionExist = file_exists($filepath . '/' . $solutionFilename . '.pdf');
		$wikiSolutionExist = file_exists($pagepath . '/' . $solutionFilename . '.txt');

		return array(
			'klausur' => $klausurExist, 
			'pdfSolution' => $pdfSolutionExist, 
			'wikiSolution' => $wikiSolutionExist,
			'isCombi' => $isCombi
		);
	}

}

?>
