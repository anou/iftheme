<?php
/**
 * Classe de traitement des dates
 * regoupe les fonctions communes d'exploitation des dates
 * - convertion de dates en différents formats
 * - récupération des infos d'une date (année, mois, jour, heure)
 * - récupération d'une date à n jours d'intervalle
 *
 * PHP versions 5
 *
 * @author		romualb <contact@romualb.com>
 * @copyright	2008-2010 romualb
 * @version 	1.7.0
 * @date 		21/04/2010
*/

	


	
	class classe_date
	{
	


/*_______________________________________________________________________________________________________________	
																				MEMBRES				
*/

		protected $jours = array(
			'FR' => array(0=>"Dimanche",1=>"Lundi",2=>"Mardi",3=>"Mercredi",4=>"Jeudi",5=>"Vendredi",6=>"Samedi",7=>"Dimanche"),
			'EN' => array(0=>"Sunday",1=>"Monday",2=>"Tuesday",3=>"Wednesday",4=>"Thursday",5=>"Friday",6=>"Saturday",7=>"Sunday"),
			'TR' => array(0=>"Pazar",1=>"Pazartesi",2=>"Salı",3=>"Çarşamba",4=>"Perşembe",5=>"Cuma",6=>"Cumartesi",7=>"Pazar")
			);
		protected $joursC = array(
			'FR' => array (0=>"Dim.",1=>"Lun.",2=>"Mar.",3=>"Mer.",4=>"Jeu.",5=>"Ven.",6=>"Sam.",7=>"Dim."),
			'EN' => array(0=>"Sun.",1=>"Mon.",2=>"Tue.",3=>"Wed.",4=>"Thu.",5=>"Fri.",6=>"Sat.",7=>"Sun."),
			'TR' => array(0=>"Pzr.",1=>"Pzt.",2=>"Sal.",3=>"Çar.",4=>"Per.",5=>"Cum.",6=>"Cts.",7=>"Pzr.")
			);
		protected $mois = array(
			'FR' => array(1=>"Janvier",2=>"Février",3=>"Mars",4=>"Avril",5=>"Mai",6=>"Juin",7=>"Juillet",8=>"Août",9=>"Septembre",10=>"Octobre",11=>"Novembre",12=>"Décembre"),
			'EN' => array(1=>"January",2=>"February",3=>"March",4=>"April",5=>"May",6=>"June",7=>"July",8=>"August",9=>"September",10=>"October",11=>"November",12=>"December"),
			'TR' => array(1=>"Ocak",2=>"Şubat",3=>"Mart",4=>"Nisan",5=>"Mayıs",6=>"Haziran",7=>"Temmuz",8=>"Ağustos",9=>"Eylül",10=>"Ekim",11=>"Kasım",12=>"Aralık")
			);
		protected $moisC = array(
			'FR' => array(1=>"Janv.",2=>"Févr.",3=>"Mars",4=>"Avr.",5=>"Mai",6=>"Juin",7=>"Juil.",8=>"Août",9=>"Sept.",10=>"Oct.",11=>"Nov.",12=>"Déc."),
			'EN' => array(1=>"Jan.",2=>"Feb.",3=>"Mar.",4=>"Apr.",5=>"May",6=>"Jun.",7=>"Jul.",8=>"Aug.",9=>"Sep.",10=>"Oct.",11=>"Nov.",12=>"Dec."),
			'TR' => array(1=>"Oca.",2=>"Şub.",3=>"Mar.",4=>"Nis.",5=>"May.",6=>"Haz.",7=>"Tem.",8=>"Ağu.",9=>"Eyl.",10=>"Eki.",11=>"Kas.",12=>"Ara.")
			);
		
		protected $lng = "FR";
		

		public $date = "";
		public $jourCourt = false;
		public $moisCourt = false;
		public $heure = false;
		public $heureCourte = false;


	
/*_______________________________________________________________________________________________________________	
																				METHODES PRIVEES				
*/
	
	
/**
		* retourne le no du jour de la semaine
		* la semaine commence le lundi (1) et se termine le dimanche (7)
*/
		protected function _getNoJourSemaine($a_jour){
			$jour = strtolower($a_jour);

			switch ($jour)
			{
				case "lundi":		case "lun":
				case "monday":		case "mon":
				case "pazartesi":	case "pts":
					$num = 1;	break;
				case "mardi":		case "mar":
				case "tuesday":		case "tue":
				case "salı":		case "sal":
					$num = 2;	break;
				case "mercredi":	case "mer":
				case "wednesday":	case "wed":
				case "çarşamba":	case "çar":
					$num = 3;	break;
				case "jeudi":		case "jeu":
				case "thursday":	case "thu":
				case "perşembe":	case "per":
					$num = 4;	break;
				case "vendredi":	case "ven":
				case "friday":		case "fri":
				case "cuma":		case "cum":
					$num = 5;	break;
				case "samedi":		case "sam":
				case "saturday":	case "sat":
				case "cumartesi":	case "cts":
					$num = 6;	break;
				case "dimanche":	case "dim":
				case "sunday":		case "sun":
				case "pazar":		case "paz":
					$num = 7;	break;
			}
			return ($num);
		}
		
		
		
		
		
/**
		* retourne le nom du mois correspondant à un no de mois
*/
		protected function _num2Month($a_mois)
		{
			$res = $this->moisCourt ? $this->moisC[$this->lng][$a_mois] : $this->mois[$this->lng][$a_mois];
			return($res);
		}
	


/**
		* retourne le no du mois
		* français et anglais + turc
*/
		protected function _month2Num($a_mois)
		{
			$num="";			
			$mois = strtolower($a_mois);

			switch (strtolower($mois))
			{
				case "janvier":			case "jan":
				case "january":
				case "ocak":			case "oca":
					$num = "01";		break;
					
				case "fevrier":			case "février":			case "fev":			case "fév":
				case "february":		case "feb":
				case "şubat":			case "şub":
					$num = "02";		break;
					
				case "mars":			case "mar":
				case "march":
				case "mart":			case "mar":
					$num = "03";		break;
					
				case "avril":			case "avr":
				case "april":			case "apr":
				case "nisan":			case "nis":
					$num = "04";		break;
					
				case "mai":
				case "may":
				case "mayıs":			
					$num = "05";		break;
					
				case "juin":			case "jun":
				case "june":
				case "haziran":			case "haz":
					$num = "06";		break;
					
				case "juillet":			case "jul":
				case "july":
				case "temmuz":			case "tem":
					$num = "07";		break;
					
				case "aout":			case "août":			case "aou":			case "aoû":
				case "august":			case "aug":
				case "ağustos":			case "ağu":
					$num = "08";		break;
					
				case "septembre":		case "sep":
				case "septeber":
				case "eylül":			case "eyl":
					$num = "09";		break;
					
				case "octobre":			case "oct":
				case "october":
				case "ekim":			case "eki":
					$num = "10";		break;
					
				case "novembre":		case "nov":
				case "november":
				case "kasım":			case "kas":
					$num = "11";		break;
					
				case "decembre":		case "décembre":		case "dec":			case "déc":
				case "december":
				case "aralık":			case "ara":
					$num = "12";		break;
			}
			
			return($num);
		}
		
		


/**
		* analyse une date et retourne un tableau de la forme:
		* array['format']
		* array['année']
		* array['mois']
		* array['jour']
		* array['heure']
		* si la date n'est pas précisée, on prend en compte le membre $date
*/
		public function _getInfosDate($a_date="")
		{
			$patternFr = "/^(lundi|lun|mardi|mar|mercredi|mer|jeudi|jeu|vendredi|ven|samedi|sam|dimanche|dim)?";
			$patternFr .= "[[:space:]]*([\d]{1,2})";
			$patternFr .= "[[:space:]]*(janvier|jan|fevrier|février|fev|fév|mars|mar|avril|avr|mai|juin|jui|juillet|aout|août|aou|aoû|septembre|sep|octobre|oct|novembre|nov|decembre|décembre|dec|déc)";
			$patternFr .= "[[:space:]]*([\d]{2,4})";
			$patternFr .= "[[:space:]]?([\d]{2}:[\d]{2}(:[\d]{2})*)?";
			$patternFr .= "$/i";

			$patternEn = "/^(monday|mon|tuesday|tue|wednesday|wed|thursday|thu|friday|fri|saturday|sat|sunday|sun)?";
			$patternEn .= "[[:space:]]*([\d]{1,2}(st|nd|rd|th))";
			$patternEn .= "[[:space:]]*(january|jan|february|feb|march|mar|april|apr|may|june|july|august|aug|september|sep|october|oct|november|nov|december|dec)";
			$patternEn .= "[[:space:]]*([\d]{2,4})";
			$patternEn .= "[[:space:]]?([\d]{2}:[\d]{2}(:[\d]{2})*)?";
			$patternEn .= "$/i";
			
			$patternTr = "/^(pazartesi|pts|salı|sal|çarşamba|çar|perşembe|per|cuma|cum|cumartesi|cts|pazar|paz)?";
			$patternTr .= "[[:space:]]*([\d]{1,2})";
			$patternTr .= "[[:space:]]*(ocak|oca|şubat|şub|mart|mar|nisan|nis|mayıs|may|haziran|haz|temmuz|tem|ağustos|ağu|eylül|eyl|ekim|eki|kasım|kas|aralık|ara)";
			$patternTr .= "[[:space:]]*([\d]{2,4})";
			$patternTr .= "[[:space:]]?([\d]{2}:[\d]{2}(:[\d]{2})*)?";
			$patternTr .= "$/i";
		
			$res=array();
			$date = strlen($a_date) ? $a_date : $this->date;

			// timestamp
			if (is_numeric($date))
			{
				$res['format'] = "UNX";
				$res['annee'] = date("Y",$date);
				$res['mois'] = date("m",$date);
				$res['jour'] = date("j",$date);
				$res['heure'] = date("H:i:s",$date);
			}
			// SQL 					2008-08-11 15:30:21
			else if (preg_match("/^([\d]{4})-([\d]{2})-([\d]{2})( [\d]{2}:[\d]{2}:[\d]{2})?$/",$date,$l_date))
			{
				$res['format'] = "SQL";
				$res['annee'] = $l_date[1];
				$res['mois'] = $l_date[2];
				$res['jour'] = $l_date[3];
				$res['heure'] = isset($l_date[4]) ? trim($l_date[4]) : "00:00:00";
			}
			// STR 					11/08/2008 ou 11/08/2008
			else if (preg_match("/^([\d]{1,2})\/([\d]{1,2})\/([\d]{2,4})([[:space:]]?[\d]{2}:[\d]{2}(:[\d]{2})*)?$/",$date,$l_date))
			{
				$res['format'] = "STR";
				$res['annee'] = $l_date[3];
				$res['mois'] = $l_date[2];
				$res['jour'] = $l_date[1];
				$res['heure'] = isset($l_date[4]) ? trim($l_date[4]) : "00:00:00";
			}
			// RSS					Mon, 11 Aug 2008 14:18:58
			else if (preg_match("/^([\w]{3}), ([\d]{1,2}) ([\w]{1,3}) ([\d]{2,4}) ([\d]{2}:[\d]{2}:[\d]{2})$/i",$date,$l_date))
			{
				$res['format'] = "RSS";
				$res['annee'] = $l_date[4];
				$res['mois'] = $this->_month2Num($l_date[3]);
				$res['jour'] = $l_date[2];
				$res['heure'] = isset($l_date[5]) ? trim($l_date[5]) : "00:00:00";
			}

			// FR 					lundi 11 aout 2008 14:18:58
			else if (preg_match($patternFr,$date,$l_date)) {
				$res['format'] = "FR";
				$res['annee'] = $l_date[4];
				$res['mois'] = $this->_month2Num($l_date[3]);
				$res['jour'] = $l_date[2];
				$res['heure'] = isset($l_date[5]) ? trim($l_date[5]) : "00:00:00";
			}


			// EN 					Monday 11th august 2008 14:18:58
			else if (preg_match($patternEn,$date,$l_date)) {
				$res['format'] = "EN";
				$res['annee'] = $l_date[4];
				$res['mois'] = $this->_month2Num($l_date[3]);
				$res['jour'] = $l_date[2];
				$res['heure'] = isset($l_date[5]) ? trim($l_date[5]) : "00:00:00";
			}

			
			// TR 					pazartesi 11 ağustos 2008 14:18:58
  		else if (preg_match($patternTr,$date,$l_date)) {
				$res['format'] = "TR";
				$res['annee'] = $l_date[4];
				$res['mois'] = $this->_month2Num($l_date[3]);
				$res['jour'] = $l_date[2];
				$res['heure'] = isset($l_date[5]) ? trim($l_date[5]) : "00:00:00";
		  }
		  
			return ($res);
		}


	

/*_______________________________________________________________________________________________________________	
																				METHODES PUBLIQUES				
*/


/**
		* constructeur
		* a_date = date
*/
		public function __construct($a_date="",$a_format="")
		{
			setlocale (LC_TIME, 'fr_FR','fra');
			$this->jourCourt=false;
			$this->moisCourt=false;
			$this->heure=false;
			if (strlen($a_date)>0)
				$this->setDate($a_date,$a_format);
		}


/**
		* définit la langue du calendrier
		* FR, EN
*/
		public function setLangue($a_langue)
		{
			$this->lng=$a_langue;
		}
/**
		* récupère la langue du calendrier
*/
		public function getLangue()
		{
			$c_langue = $this->lng;
			
			//Only EN, FR and TR exist
			switch($c_langue){
  			case 'FR':
  			case 'EN':
			case 'TR':
  			  $c_langue = $c_langue;
  		  break;
  		  default://default to EN
  		    $c_langue = 'EN';
			}
			
			return $c_langue;
		}



/**
		* définit / change la date en cours
*/
		public function setDate($a_date,$a_format="")
		{
			$infos = $this->_getInfosDate($a_date);
			if ($infos['heure']!="00:00:00")
				$this->setHeure();
			$this->date = $this->convert($a_format,$a_date);
		}


/**
		* retourne la date en cours
*/
		public function getDate($a_format="")
		{
			$date = $this->convert($a_format);
			return ($date);
		}

	
/**
		* retourne l'année d'une date
		* si la date n'est pas précisée, on prend en compte le membre $date
*/
		public function getAnnee($a_date="")
		{
			$date = strlen($a_date)>0 ? $a_date : $this->date;
			$infos = $this->_getInfosDate($date);
			if(isset($infos['annee']))
			return($infos['annee']);
		}
		
/**
		* retourne le no de mois d'une date
		* si la date n'est pas précisée, on prend en compte le membre $date
*/
		public function getMois($a_date="")
		{
			$date = strlen($a_date)>0 ? $a_date : $this->date;
			$date = $this->convert("UNX",$date);
			return(date("m",$date));
		}

/**
		* retourne le nom du mois d'une date
		* si la date n'est pas précisée, on prend en compte le membre $date
*/
		public function getNomMois($a_date="")
		{
			$noMois = intval($this->getMois($a_date));
			return ($this->moisCourt ? $this->moisC[$this->lng][$noMois] : $this->mois[$this->lng][$noMois]);
		}
		
/**
		* retourne le no de semaine d'une date
		* si la date n'est pas précisée, on prend en compte le membre $date
*/
		public function getSemaine($a_date="")
		{
			$date = strlen($a_date)>0 ? $a_date : $this->date;
			$date = $this->convert("UNX",$date);
			return(date("W",$date));
		}

/**
		* retourne le jour d'une date
		* si la date n'est pas précisée, on prend en compte le membre $date
*/
		public function getJour($a_date="")
		{
			$date = strlen($a_date)>0 ? $a_date : $this->date;
			$infos = $this->_getInfosDate($date);
			return($infos['jour']);
		}

/**

		* retourne le nom du jour d'une date
		* si la date n'est pas précisée, on prend en compte le membre $date
*/
		public function getNomJour($a_date="")
		{
			$date = strlen($a_date)>0 ? $a_date : $this->date;
			$date = $this->convert("UNX",$date);
			return ($this->jourCourt ? $this->joursC[$this->lng][gmdate("w",$date)] : $this->jours[$this->lng][gmdate("w",$date)]);
		}
		
/**
		* retourne le format d'une date
		* si la date n'est pas précisée, on prend en compte le membre $date
*/
		public function getFormat($a_date="")
		{
			$date = strlen($a_date) ? $a_date : $this->date;
			$infos = $this->_getInfosDate($date);
			return($infos['format']);
		}


/**
		* vérifie si une année est bisextile
		* si l'annee $a_annee est précisée, on test sur cette année
		* sinon on teste sur le membre $date
*/
		public function isBisextile($a_annee="")
		{
			$date = strlen($a_annee) ? gmmktime(1, 0, 0, 1, 1, $a_annee) : $this->convert("UNX",$this->date);
			return(date("L",$date));
		}

/**
		* nombre de jours dans le mois
		* si le mois et l'annee sont précisés, on prend en compte ces valeurs
		* si l'année n'est pas précisée, on prend en compte l'année du membre $date
		* sinon on teste sur le membre $date
		* $a_mois : no du mois ou mois littéral
		* $a_annee : année YYYY
*/
		public function getJoursMois($a_mois="", $a_annee="")
		{
			// on enleve le décalage horaire
			if (strlen($a_mois)>0)
			{
				$mois = ($a_mois>=1 && $a_mois<=12) ? $a_mois : $this->_month2Num($a_mois);
			}
			$date = strlen($a_mois)>0 ? gmmktime(12, 0, 0, intval($mois), 1, (strlen($a_annee)>0 ? $a_annee : $this->getAnnee())) : $this->convert("UNX",$this->date);
			return (date("t",$date));
		}
		




		/**
		  * retourne la date d'un jour (lundi, mardi...) de la semaine 
		  * $a_semaine = no de semaine
		  * $a_annee = année, par défaut celle du membre $date
		  * $a_jour = jour recherché (par défaut, lundi)
		  * $a_format unix par défaut
		*/  
		 
		public function getJourSemaine($a_semaine,$a_jour="lundi",$a_format="UNX",$a_annee="")
		{
			if (strlen($a_annee)==0)
				$a_annee = $this->getAnnee();
			if(strftime("%W",gmmktime(0,0,0,01,01,$a_annee))==1)
				$mon_mktime = gmmktime(0,0,0,01,(01+(($a_semaine-1)*7)),$a_annee);
			else
				$mon_mktime = gmmktime(0,0,0,01,(01+(($a_semaine)*7)),$a_annee);
			
			// le 04 janvier est toujours en première semaine
			// on cherche le jour du 04 janvier pour calculer le décalage
			$decalage = (date("w",$mon_mktime)-1)*60*60*24;
			$quatreJan = date("w",gmmktime(1,0,0,1,4,$a_annee));
		
			// si le 04 janvier tombe du jeudi au dimanche
			if ($quatreJan==0 || $quatreJan>=4)
			{
				// si le 1er janvier tombe un lundi
				if (date("w",gmmktime(1,0,0,1,1,$a_annee))==1)
					$decalage = $decalage;
				else 
					$decalage = $decalage + (7*60*60*24);
			}
			// sinon si le 1er janvier tombe un dimanche
			else if (date("w",gmmktime(1,0,0,1,1,$a_annee))==0)
			{
				
				$decalage = $decalage + (7*60*60*24);
			}	
			$noJour = $this->_getNoJourSemaine($a_jour)-1;
			$jour = $mon_mktime - $decalage + ($noJour*60*60*24);
			$date = $this->convert($a_format,$jour);
			
			return ($date);
		}
		
		
		
/**
		* nombre de jours entre deux dates
*/		
		public function getJoursPeriode($a_dateFrom,$a_dateTo)
		{
			$a_dateFrom = $this->convert("UNX",$a_dateFrom);
			$a_dateTo = $this->convert("UNX",$a_dateTo);
			return ( (($a_dateTo-$a_dateFrom)/60/60/24) + 1);
		}
		
		
	
/**
		* convertion d'une date
		* $a_format : format voulu (par défaut, le même que la date)
		* $a_date : la date à convertir si différente de $date
		* SQL		:	YYYY-MM-JJ H:i:s
		* STR		:	JJ/MM/YYYY H:i:s
		* FR		:	Jour JJ Mois YYYY
		* EN		:	Jour JJth Mois YYYY
		* TR		:	Jour JJ Mois YYYY
		* UNX		:	timestamp unix
		* URL		:	YYYY/MM/JJ
		* USR		:	MM/JJ/YYYY (pour commande linux useradd)
		* RSS		:	Mon, 11 Aug 2008 14:18:58 (RFC822)
*/		
		public function convert($a_format="",$a_date="")
		{
			$res = false;
			// analyse de la date saisie
			$l_date = $this->_getInfosDate(strlen($a_date)>0 ? $a_date : $this->date);
			if (strlen($a_format)==0) $a_format = $l_date['format'];
			if (isset($l_date['format']))
			{
				$heures = explode(":",$l_date['heure']);			
				if ($this->heure)
					$timestamp = gmmktime($heures[0],$heures[1],(isset($heures[2]) ? $heures[2] : 0),$l_date['mois'],$l_date['jour'],$l_date['annee']);
				else 
					$timestamp = gmmktime(0,0,0,$l_date['mois'],$l_date['jour'],$l_date['annee']);
				switch($a_format)
				{
					case "SQL":
						$res = gmdate("Y-m-d".($this->heure ? " H:i:s": ""),$timestamp);
						break;
					case "STR":
						$res = gmdate("d/m/Y".($this->heure ? " H:i".(!$this->heureCourte ? ":s": ""): ""),$timestamp);
						break;
					case "FR":
						$res =  ($this->jourCourt ? $this->joursC['FR'][gmdate("w",$timestamp)] : $this->jours['FR'][gmdate("w",$timestamp)]) . 
								gmdate(" d ",$timestamp) . 
								($this->moisCourt ? $this->moisC['FR'][gmdate("n",$timestamp)] : $this->mois['FR'][gmdate("n",$timestamp)]) . 
								gmdate(" Y".($this->heure ? " H:i".(!$this->heureCourte ? ":s": ""): ""),$timestamp);
						break;
					case "TR":
						$res =  ($this->jourCourt ? $this->joursC['TR'][gmdate("w",$timestamp)] : $this->jours['TR'][gmdate("w",$timestamp)]) . 
								gmdate(" d ",$timestamp) . 
								($this->moisCourt ? $this->moisC['TR'][gmdate("n",$timestamp)] : $this->mois['TR'][gmdate("n",$timestamp)]) . 
								gmdate(" Y".($this->heure ? " H:i".(!$this->heureCourte ? ":s": ""): ""),$timestamp);
						break;
					case "EN":
						$day = gmdate(" d",$timestamp);
						if (preg_match("/1$/",$day))		$jour = $day."st ";
						else if (preg_match("/2$/",$day))	$jour = $day."nd ";
						else if (preg_match("/3$/",$day))	$jour = $day."rd ";
						else $jour = $day."th ";
						$res =  ($this->jourCourt ? $this->joursC['EN'][gmdate("w",$timestamp)] : $this->jours['EN'][gmdate("w",$timestamp)]) . 
								 $jour.
								($this->moisCourt ? $this->moisC['EN'][gmdate("n",$timestamp)] : $this->mois['EN'][gmdate("n",$timestamp)]) . 
								gmdate(" Y".($this->heure ? " H:i".(!$this->heureCourte ? ":s": ""): ""),$timestamp);
						break;
					case "UNX":
						$res = $timestamp;
						break;
					case "URL":
						$res = gmdate("Y/m/d",$timestamp);
						break;
					case "USR":
						$res = gmdate("m/d/Y",$timestamp);
						break;
					case "RSS":
						$res = gmdate("D, d M Y H:i:s",$timestamp);
						break;
				}
			}
			return($res);
		}


/**
		* prise en compte du format de jour court
*/
		public function setJourCourt($a_bool=true)
		{
			$this->jourCourt = $a_bool;
		}

/**
		* prise en compte du format de jour court
*/
		public function setMoisCourt($a_bool=true)
		{
			$this->moisCourt = $a_bool;
		}


/**
		* prise en compte de l'heure
*/
		public function setHeure($a_bool=true)
		{
			$this->heure = $a_bool;
		}


/**
		* affichage de l'heure sans les secondes
*/
		public function setHeureCourte($a_bool=true)
		{
			$this->heure = true;
			$this->heureCourte = $a_bool;
		}






/**
		* retourne la date a n jours d'ecart
		* $a_nbJours = nb de jours d'écart
		* $a_format = format de retour (le même que $this->date si pas précisé
*/
		public function getDateFrom($a_nbJours,$a_format="",$a_date="")
		{
			$l_date = strlen($a_date)>0 ? $a_date : $this->date;
			// format de date
			if (strlen($a_format)==0)
			{
				$format = $this->_getInfosDate($l_date);
				$a_format = $format['format'];
			}
			// 
			$unxDate = $this->convert("UNX",$l_date);
			$unxNewDate = $unxDate + $a_nbJours*24*60*60;

			return ($this->convert($a_format,$unxNewDate));
		}
	




}	


?>
