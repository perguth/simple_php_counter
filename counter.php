<?php

class spc
{
	// -------------- Konfiguration --------------------
	public $besuche_zusammenfassen = true;	// summarize page impressions by one single user
	public $nur_im_source_anzeigen = true;	// display only in source
	public $dateipfad_zur_txt = 'spc/';
	public $sprache = 'de';		// en
	public $bots_filtern = true;	// dont count bots
	public $counter_nummer = 1;	// if several independant counters should be used within
					// one singe page
	public $email = 'none';		// for statistcs
	public $email_nach_tagen = 7;	// send stats every X days
	// -------------------------------------------------
	// $data[0 - 7] <- Days
	// $data[8] <- Total 
	// $data[9] <- Last Visit
	// $data[10] <- First  Visit
	// $data[11] <- Date of the last email
	// $date[12] <- Last eight Days
	// -------------------------------------------------
	private $sessionvariable;
	private $data;
	private $letzten_acht_tage = 'NaN';
	private $keine_session;
	private $maschiene = false;
	private $dateipfad;
	private $ausgabe; // everything except <pre> and <!--
	// -------------------------------------------------
	private function letzten_acht_tage($x=7)
	{
		if( $x >= 0 )
			return( $this->data[$x] + $this->letzten_acht_tage($x-1) );
	}
	private function ascii_grafik($x)
	{
		if( $this->letzten_acht_tage == "NaN" )
		{
			if( isset($this->keine_session) or ! $this->besuche_zusammenfassen )
				$this->letzten_acht_tage = $this->letzten_acht_tage();
			else
			{
				if( ! isset( $_SESSION[$this->sessionvariable][12] ) )
					$_SESSION[$this->sessionvariable][12] = $this->letzten_acht_tage = $this->letzten_acht_tage();
				else
					$this->letzten_acht_tage = strip_tags($_SESSION[$this->sessionvariable][12]);
			}
		}
		// -----------------------------------------
		$this->ausgabe .= " [";
		for($i=0; $i < 10; $i++)
		{
			if ( $i < round(($this->data[$x] / $this->letzten_acht_tage)*30) )
			{
				$this->ausgabe .= "|";
			}
			else
			{
				$this->ausgabe .= ".";
			}
		}
		$this->ausgabe .= "] ";
		if( $this->data[$x] != 0 )
			$this->ausgabe .= $this->data[$x];
	}
	private function data_fuellen()
	{
		for($i=0; $i<9; $i++)
			$this->data[$i] = 0;
		$this->data[9] = $this->data[10] = date("d.m.Y");
	}
	// -------------------------------------------------
	public function start()
	{
		if( ! isset($_SESSION[$this->sessionvariable]) )
		{
			$this->sessionvariable = basename(dirname($_SERVER["SCRIPT_FILENAME"]))
				. "_" . basename($_SERVER["PHP_SELF"],".php") . "_" . $this->counter_nummer;
			
			if( $this->bots_filtern && ! isset($_SESSION[$this->sessionvariable]) )
			{
				$tmp = array("spider","spyder","bot","crawl","robo","agentname");
				foreach( $tmp as $wert )
					if( false !== strpos($_SERVER["HTTP_USER_AGENT"], $wert) )
					{
						$this->maschiene = true;
						break;
					}
			}
			$this->dateipfad = $this->dateipfad_zur_txt . $this->sessionvariable . ".txt";
			if( ! is_file($this->dateipfad) )
			{
				mkdir($this->dateipfad_zur_txt);
				chmod($this->dateipfad_zur_txt, 0755);
				$tmp = fopen($this->dateipfad,"w");
				if( $tmp )
				{
					$this->data_fuellen();
					fwrite($tmp,serialize($this->data));
					fclose($tmp);
				}
			}
		}
		if( ! $this->maschiene )
		{
			$heute = mktime(0,0,0,date("m"),date("d"),date("Y"));
			// -----------------------------------------
			if( ! isset($_SESSION[$this->sessionvariable]) or ! $this->besuche_zusammenfassen )
			{
				if( session_id() == "")
				{
					$this->keine_session = true;
				}
				// ---------------------------------
				$tmp = fopen($this->dateipfad,"r");
				if( ! $tmp )
				{
					$unlesbar = true;
				}
				else
				{
					$this->data = unserialize(fread($tmp,filesize($this->dateipfad)));
					fclose($tmp);
					// --------------------------------
					$tmp = explode(".",$this->data[10]);
					$tmp = mktime(0,0,0,(int) $tmp[1],(int) $tmp[0],(int) $tmp[2]);
					$tmp = $heute - $tmp;
					$tmp = $tmp / 86400;
					if( $tmp == 0 )
						$tage_seit_erstem_aufruf = 1;
					else
					{
						$tage_seit_erstem_aufruf = $tmp;
						if( ! isset($this->data[11]) )
							$this->data[11] = date("d.m.Y");
						if( $this->email != "none" && $this->data[11] != date("d.m.Y") )
							if( $tage_seit_erstem_aufruf % $this->email_nach_tagen == 0 )
							{
								$send_email = true;
								$this->data[11] = date("d.m.Y");
							}
					}
					// --------------------------------
					if( $this->data[9] != date("d.m.Y") )
					{
						$tmp = explode(".",$this->data[9]);
						$letzter_aufruf = mktime(0,0,0,$tmp[1],$tmp[0],$tmp[2]);
						$tmp = $heute - $letzter_aufruf;
						$tage_seit_letztem_aufruf = $tmp / 86400;
						// ------------------------
						if ( $tage_seit_letztem_aufruf > 7 )
						{
							for ( $i=0; $i < 8; $i++ )
							{
								$this->data[8] += $this->data[$i];
								$this->data[$i] = 0;
							}
						}
						else
						{
							for ( $i=0; $i < $tage_seit_letztem_aufruf; $i++ )
							{
								$this->data[8] += $this->data[7];
								for ( $h=7; $h > 0; $h-- )
								{
										$this->data[$h] = $this->data[$h - 1];
								}
								$this->data[$i] = 0;
							}
							
						}
						$this->data[9] = date("d.m.Y");
					}
					$this->data[8] ++;
					$this->data[0] ++;
					if( ! isset($unlesbar) )
					{
						$tmp = fopen($this->dateipfad,"w");
						fwrite($tmp,serialize($this->data));
						fclose($tmp);
						if( ! isset($this->keine_session) && $this->besuche_zusammenfassen )
							$_SESSION[$this->sessionvariable] = $this->data;
					}
				}
			}
			else
				for($i=0; $i<11; $i++)
					$this->data[$i] = strip_tags($_SESSION[$this->sessionvariable][$i]);
			// -----------------------------------------
			switch($this->sprache)
			{
				case "de":
					$txt_heute = "Heute ....................";
					$txt_gestern = "Gestern ..................";
					$txt_vor = "Vor ";
					$txt_tagen = " Tagen ..............";
					$txt_in_den_letzten_acht_tagen = "In den letzten 8 Tagen: ..";
					$txt_gesamt = "Gesamt ...................";
					$txt_keine_session = "Keine Session gestartet. "
						. "Mehrfachbesuche koennen nicht zusammengefasst werden.";
					$txt_nicht_lesbar = "Textdatei nicht lesbar.";
					$txt_gezaehlt_seit = "Gezaehlt seit ............";
					$txt_gezaehlt_seit_datum = $this->data[10];
					$txt_besucher_pro_tag = "Besucher pro Tag .........";
				break;
				case "en":
					$txt_heute = "Today ....................";
					$txt_gestern = "Yesterday ................";
					$txt_vor = "";
					$txt_tagen = " days ago ...............";
					$txt_in_den_letzten_acht_tagen = "In the last 8 days .......";
					$txt_gesamt = "Total ....................";
					$txt_keine_session = "No session set. Visits can not be summarized.";
					$txt_nicht_lesbar = "Textfile not readable.";
					$txt_gezaehlt_seit = "Counted since ............";
					$tmp = explode(".",$this->data[10]);
					$txt_gezaehlt_seit_datum = $tmp[1] . "/" . $tmp[0] . "/" . $tmp[2];
					$txt_besucher_pro_tag = "Visitors per day .........";
				break;
			}
			// -----------------------------------------
			if ( $this->nur_im_source_anzeigen )
			{
				echo "\n" . "<!--" . "\n";
			}
			else
				echo "\n" . "<pre>";
			$this->ausgabe .= $txt_heute;
			$this->ascii_grafik(0);
			$this->ausgabe .= "\n"
				. ".........................."
				. "\n"
				. $txt_gestern;
			$this->ascii_grafik(1);
			for($i=2; $i < 8; $i++)
			{
				$this->ausgabe .= "\n"
					. $txt_vor . $i . $txt_tagen;
				$this->ascii_grafik($i);
			}
			$this->ausgabe .= "\n"
				. $txt_in_den_letzten_acht_tagen . " ............ " . $this->letzten_acht_tage
				. "\n"
				. ".........................."
				. "\n"
				. $txt_gesamt . " ............ " . $this->data[8]
				. "\n"
				. $txt_gezaehlt_seit . "  " . $txt_gezaehlt_seit_datum
				. "\n"
				. $txt_besucher_pro_tag . " ............ ";
			$tmp = explode(".",$this->data[10]);
			$tmp = mktime(0,0,0,(int) $tmp[1],(int) $tmp[0],(int) $tmp[2]);
			$tmp = $heute - $tmp;
			$tmp = $tmp / 86400;
			if( $tmp == 0 )
				$tage_seit_erstem_aufruf = 1;
			else
				$tage_seit_erstem_aufruf = $tmp;
			$tmp = round($this->data[8] / $tage_seit_erstem_aufruf, 1);
			if($this->sprache == 'de')
				$this->ausgabe .= str_replace('.', ',', $tmp);
			else
				$this->ausgabe .= $tmp;
			if( isset($this->keine_session) )
				$this->ausgabe .= "\n" . "\n" . $txt_keine_session;
			if( isset($unlesbar) )
				$this->ausgabe .= "\n" . "\n" . $txt_nicht_lesbar;
			echo $this->ausgabe;
			if ( ! $this->nur_im_source_anzeigen )
				echo "</pre>";
			echo "\n" . "\n";
			if ( ! $this->nur_im_source_anzeigen )
				echo "<!--";
			echo "\n"
				. "simple php counter v.27 ~ .txt powered"
				. "\n"
				. "http://d3velop.net/scripts/php/spc/"
				. "\n"
				. "-->"
				. "\n"; 
			if( $send_email )
				mail($this->email, "SPC: ".$_SERVER["PHP_SELF"], $this->sessionvariable."\n"."\n".$this->ausgabe);
		}
		// -------------------------------------------------
	}
}
// EOF