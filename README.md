Exam Plugin
============

Dies ist ein Dokwuikiplugin, um Benutzern eine einfach Möglichkeit zum
Up- und Download von Klausuren und deren Lösungen zu geben.


Wichtiger Hinweis
-----------------

Der Inhalt dieses Git Repositories muss wie folgt gespeichert werden:

	DOKUWIKIPATH/lib/plugins/klausuren/

Das Pluginverzeichnis MUSS den Namen **klausuren** haben, damit das Plugin funktioniert.

Lizenz
------

Das Projekt ist unter MIT lizensiert, enthält jedoch Teile von Dritten, die nicht unter MIT stehen.
Die Datei LICENSE beinhaltet weitere Informationen.


Funktionsweise
--------------

Zunächst muss in den Einstellunngen ein Upload Namespace festgelegt werden. In diesen Namespace
werden anschließend alle Klausuren und Lösungen hochgeladen. Ebenso werden die Wiki Lösungsseiten
in diesem liegen. Wir verwenden dafür z.B. :vorlesungen:unterlagen:.

Anschließend kann auf jeder beliebigen Wikiseite folgender Tag verwendet werden:

	{{klausuren>FACHNAME}}

Fachname kann eine beliebige Zeichenkette aus Buchstaben und Ziffern sein. Für dieses Fach wird
in dem unter Einstellungen gewählten Namespace nun ein neuer Namespace eröffnet in den alle Dateien
hochgeladen werden. Ebenso werden als Pages in diesem Namespace Seiten anegelegt für die Wikilösungen.
Sind ein paar Klaususren hochgeladen, kann die Struktur dann beispielsweise wie folgt aussehen:

	+ Unterlagen
	|+fach1
	||-fach1_2009ss_klausur.pdf
	||-fach1_2009ss_loesung.pdf
	||-fach1_2010ws_klausur.pdf
	||-fach1_2010ws_loesung [Wikiseite]
	|+fach2
	||-fach2_2009ss_klausur.pdf
	||-fach2_2009ss_loesung.pdf
