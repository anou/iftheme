<?php
/**
 * Classe calendrier
 * 
 * hérite de la classe de gestion de dates
 *
 * PHP versions 5
 *
 * @author		romualb <contact@romualb.com>
 * @copyright	2008-2010 romualb
 * @version 	1.3.2
 * @date 		21/04/2010
 *
*/

	


	
	class classe_calendrier extends classe_date
	{
	

/*_______________________________________________________________________________________________________________	
																				MEMBRES				
*/

		private $calName = "cal_1";					// nom du calendrier

		private $lienMois = "";						// activation des liens dur le mois
		private $liensSemaines = "";				// activation des liens dur les no de semaine
		private $liensJours = "";					// activation des liens dur les jours
		
		private $targetLiens = "";					// cible des liens du calendrier
		private $targetNavig = "";					// cible des liens de navigation
		
		private $navigMois = true;					// affichage du mois
		private $cmois = true;						// mois
		private $cjours = true;						// jours
		private $csemaines = false;					// semaines
		private $cevents = false;					// affichage des evenements

		private $lienMoisActif = false;				//
		private $liensSemainesActif = false;		//
		private $liensJoursActif = false;			//
		private $futur = false;						// active les jours futurs
		private $passe = false;						// active les jours passés
		private $present = false;					// active le jour courant
		private $dateMin = "";						// date min active
		private $dateMax = "";						// date max active
		
		private $events = array();					// liste des evenements
		private $ajax = false;						// activation ajax
		private $ajaxDiv = "";						// nom du div ajax
		private $ajaxScript = "";					// ecriture du script ajax

		
	
/*_______________________________________________________________________________________________________________	
																				METHODES PRIVEES				
*/
	
	
	/**
	* function javascript de mise à jour de l'affichage du calendrier en ajax
*/
		private function _ecritFonctionAjax()
		{
			$codeJs = "";
			$codeJs .= "<script language=\"javascript\">";
				// mise à jour du calendrier
				$codeJs .= "function navigCalendrier(annee,mois,lang)";
				$codeJs .= "{";
					$codeJs .= "var ajax = new XHR();";
					$codeJs .= "ajax.appendData('annee',annee);";
					$codeJs .= "ajax.appendData('mois',mois);";
					$codeJs .= "ajax.appendData('lang',lang);";
					$codeJs .= "ajax.send('".$this->ajaxScript."');";
					$codeJs .= "ajax.complete = function (xhr)";
					$codeJs .= "{";
						$codeJs .= "document.getElementById('".$this->ajaxDiv."').innerHTML = xhr.responseText;";
					$codeJs .= "}";
				$codeJs .= "}";
			$codeJs .= "</script>";
			print $codeJs;
		}


/**
		* ecriture des différents liens
		* $a_lien = page
		* a_mois = no de mois
		* a_annee = annee sur 4 chiffres
		* a_jour = no du jour
		* a_semaine = no de semaine
*/
	
		private function _ecritLien($a_lien,$a_annee,$a_mois=0,$a_jour=0,$a_semaine=0)
		{
			$lien = $a_lien;
			$lien .= "calname=".$this->calName;
			$lien .= "&annee=".$a_annee;
			if ($a_mois>0)
				$lien .= "&mois=".$a_mois;
			if ($a_jour>0)
				$lien .= "&jour=".$a_jour;
			if ($a_semaine>0)
				$lien .= "&semaine=".$a_semaine;
			
			return($lien);
		}

	
/*_______________________________________________________________________________________________________________	
																				METHODES PUBLIQUES				
*/
	
	
/**
		* constructeur
		* a_date = date
*/
		public function __construct($a_name="")
		{
			$this->lienMois = basename($_SERVER['PHP_SELF'])."?";
			$this->lienSemaines = basename($_SERVER['PHP_SELF'])."?";
			$this->lienJours = basename($_SERVER['PHP_SELF'])."?";
			if (strlen($a_name)>0)
				$this->calName = $a_name;
		}
	




/**
		* initialisation de l'url du lien sur le mois
*/
		public function setLienMois ($a_lien)
		{
			$this->lienMois = $a_lien;
		}


/**
		* initialisation de l'url du lien sur les semaines
*/
		public function setLienSemaines ($a_lien)
		{
			$this->lienSemaines = $a_lien;
		}


/**
		* initialisation de l'url du lien sur les jours
*/
		public function setLienJours ($a_lien)
		{
			$this->lienJours = $a_lien;
		}



/**
	* initialisation du format d'url des liens sur les jours
*/
		public function setFormatLienJours ($a_format)
		{
			$this->formatLienJours = $a_format;
		}

/**
	* initialisation du format d'url des liens sur les semaines
*/
		public function setFormatLienSemaines ($a_format)
		{
			$this->formatLienSemaines = $a_format;
		}

/**
	* initialisation du format d'url des liens sur les mois
*/
		public function setFormatLienMois ($a_format)
		{
			$this->formatLienMois = $a_format;
		}


/**
	* initialisation de l'attribut target des liens
*/
		public function setTargetLiens ($a_target="")
		{
			if (strlen($a_target)>0)
				$this->targetLiens = " target=\"".$a_target."\"";
		}


/**
	* initialisation de l'attribut target des liens de navigation
*/
		public function setTargetNavig ($a_target="")
		{
			if (strlen($a_target)>0)
				$this->targetNavig = " target=\"".$a_target."\"";
		}



/**
	* date limite inférieure de calendrier
*/
		public function setDateMin($a_date)
		{
			$this->dateMin = parent::convert("UNX",$a_date);
		}

/**
	* date limite supérieure de calendrier
*/
		public function setDateMax($a_date)
		{
			$this->dateMax = parent::convert("UNX",$a_date);
		}


/**
	* active le mode ajax
	* $a_nomDiv = nom du div mis à jour par méthode ajax
	* $a_nomScript = nom du script php qui génère le calendrier
*/
		public function activeAjax($a_nomDiv,$a_nomScript)
		{
			$this->ajax = true;
			$this->ajaxDiv = $a_nomDiv;
			$this->ajaxScript = $a_nomScript;
			$this->_ecritFonctionAjax();
		}


/**
	* afiche ou non la navig sur les mois
*/
		public function afficheNavigMois ($a_bool=true)
		{
			$this->navigMois = $a_bool;
		}
/**
	* afiche ou non la barre de mois
	* si elle est pas activée, on desactive aussi la navig sur les mois
*/
		public function afficheMois ($a_bool=true)
		{
			$this->cmois = $a_bool;
			if ($a_bool==false)
				$this->afficheNavigMois (false);
		}
/**
	* afiche ou non les semaines
*/
		public function afficheSemaines ($a_bool=true)
		{
			$this->csemaines = $a_bool;
		}
/**
	* afiche ou non les jours littéraux
*/
		public function afficheJours ($a_bool=true)
		{
			$this->cjours = $a_bool;
		}

/**
	* affiche les évenements dans le calendrier
*/
		public function afficheEvenements ()
		{
			$this->cevents = true;
		}
		
/**
	* active ou non le lien sur le mois
*/
		public function activeLienMois ()
		{
			$this->lienMoisActif = true;
		}
/**
	* active ou non les lien sur les semaines
*/
		public function activeLiensSemaines ()
		{
			$this->liensSemainesActif = true;
		}
/**
	* active ou non les lien sur les jours
*/
		public function activeLiensJours ()
		{
			$this->liensJoursActif = true;
		}

/**
	* active ou non les lien après la date du jour
*/
		public function activeJoursFuturs ()
		{
			$this->futur = true;
		}
		
/**
	* active ou non les lien avant la date du jour
*/
		public function activeJoursPasses ()
		{
			$this->passe = true;
		}

/**
	* active ou non le jour présent
*/
		public function activeJourPresent ()
		{
			$this->present = true;
		}
/**
	* active les jours pour lesquels il y a un événement
*/
		public function activeJoursEvenements ()
		{
			$this->jevent = true;
		}



/**
	* liste des évenements
	* le evenements sont stockes dans un tableau de la forme :
	* $m_events['AAAA-MM-JJ HH:MM:SS']['evenement']
*/
		public function ajouteEvenement ($a_date,$a_event)
		{
			$date = parent::convert("SQL",$a_date);
			if (!isset($this->events[$date])) 
				$i=0;
			else 
				$i=count($this->events[$date]);
			$this->events[$date][$i]['event'] = $a_event;
			$this->afficheEvenements();
		}
		

/**
		* affiche le calendrier du mois en cours
*/
		public function makeCalendrier($a_annee,$a_mois)
		{
			
			// intialisation des code pour les divs des evenements
			$codeEvent="";
			$onclick="";
			
			// si on n'affiche pas les jours futurs et présents, on désactive les liens sur les semaines
			if (!$this->futur && !$this->passe)
				$this->liensSemainesActif = false;
		
			
			$date = mktime(0,0,0,$a_mois,1,$a_annee);
			$premJour = parent::_getInfosDate($date);
			$moisCur = intval($a_mois);
			
			// limites
			if ($this->dateMin)
			{
				$moisMin = intval(parent::getMois($this->dateMin));
				$semaineMin = sprintf("%s-%s",$this->getAnnee($this->dateMin),$this->getSemaine($this->dateMin));
			}
			if ($this->dateMax)
			{
				$moisMax = intval(parent::getMois($this->dateMax));
				$semaineMax = sprintf("%s-%s",$this->getAnnee($this->dateMax),$this->getSemaine($this->dateMax));
			}
			
			// premier jour du mois
			$decal = parent::_getNoJourSemaine(date("l",$date));
			// nombre de jours
			$max = parent::getJoursMois($a_mois,$a_annee);
			// mois précédent
			if (intval($a_mois)==1)
			{ 
				$mprec=12;
				$aprec=$a_annee-1;
				$asuiv=$a_annee;
				$msuiv=$a_mois+1;
			}
			// mois précédent
			else if ($a_mois==12)
			{ 
				$msuiv=1;
				$asuiv=$a_annee+1;
				$aprec=$a_annee;
				$mprec=$a_mois-1;
			}
			else 
			{
				$asuiv=$a_annee;
				$aprec=$a_annee;
				$msuiv=$a_mois+1;
				$mprec=$a_mois-1;
			}



			$code =  "<div id=\"calendrier\">\n";
			$code .=  "<table>\n";

			// nombre de colonnes
			$nbCols = $this->csemaines ? 8 : 7;

			// ligne du mois et navigation entre les mois
			if ($this->cmois)
			{
					
				if (isset($this->formatLienMois))
					$lienMois = sprintf($this->formatLienMois,$a_annee,$a_mois);
				else
					$lienMois = $this->_ecritLien($this->lienMois,$a_annee,$a_mois);
	
				// lien ajax
				if ($this->ajax)
				{
				  $langue = isset($_POST['lang']) ? $_POST['lang'] : str_ireplace('-', '_', get_bloginfo('language'));
					$lienMoisPrec = $lienMoisSuiv = "";
					$onclickPrec = " onclick=\"navigCalendrier('".$aprec."','".$mprec."','".$langue."');\"";
					$onclickSuiv = " onclick=\"navigCalendrier('".$asuiv."','".$msuiv."','".$langue."');\"";
				}
				else 
				{
					if (isset($this->formatLienMois))
					{
						$lienMoisPrec = sprintf($this->formatLienMois,$aprec,$mprec);
						$lienMoisSuiv = sprintf($this->formatLienMois,$asuiv,$msuiv);
					}
					else
					{
						$lienMoisPrec = $this->_ecritLien($this->lienMois,$aprec,$mprec);
						$lienMoisSuiv = $this->_ecritLien($this->lienMois,$asuiv,$msuiv);
					}
					
					$onclickPrec = $onclickSuiv = "";
				}
				$colspan = $this->navigMois ?  ($nbCols-2) : $nbCols ;
				
				
			
			
			// ligne des jours litéraux
			if ($this->cjours) {
				$code .=  "<tr>\n";
				if ($this->csemaines)
					$code .=  '<th>&nbsp;</th>';
					
				for ($j=1; $j<=7; $j++) {
					//$code .=  '<th class="jour">' . substr($this->jours[$this->lng][$j],0,3) . '</th>';
					$code .=  '<th class="jour">' . $this->joursC[$this->lng][$j] . '</th>';
				}
				
				$code .=  "</tr>\n";
			}
			
			// no de semaine première ligne
			if ($this->csemaines)
			{
				$semaine = gmdate("Y-W",mktime(12,0,0,$a_mois,1,$a_annee));
				$noSemaine = gmdate("W",mktime(12,0,0,$a_mois,1,$a_annee));
				$curSemaine = gmdate("Y-W");
				$annee = $a_mois==12 && $semaine==1 ? $a_annee+1 : $a_annee;
				if (isset($this->formatLienSemaines))
					$lienSemaine = sprintf($this->formatLienSemaines,$a_annee,$semaine);
				else
					$lienSemaine = $this->_ecritLien($this->lienSemaines,$annee,0,0,$noSemaine);
				
				if (
				!$this->liensSemainesActif
				|| (isset($this->dateMin,$semaineMin) && $semaine < $semaineMin)
				|| (isset($this->dateMax,$semaineMax) && $semaine > $semaineMax)
				|| (!$this->futur && $semaine > $curSemaine) 
				|| (!$this->passe && $semaine < $curSemaine)
				)
					$code .=  "<tr><th>" . $noSemaine . "</th>";
				else
					$code .=  "<tr><th><a href=\"".$lienSemaine."\"".$this->targetLiens.">" . $noSemaine . "</a></th>";
			}
			
			
			// jours du mois
			for ($i=1; $i<43; $i++)
			{
				$jour = $i-$decal+1;
				$jourStr = sprintf("%04d-%02d-%02d",$a_annee,$a_mois,$jour);
				$jourUnx = parent::convert("UNX",$jourStr);
				
				if (isset($this->formatLienJours))
					$lienJour = sprintf($this->formatLienJours,$a_annee,$a_mois,$jour);
				else
					$lienJour = $this->_ecritLien($this->lienJours,$a_annee,$a_mois,$jour);

				// affichage du jour
				if ($jour>0 && $jour<=$max)
				{
					$event = false;
					// jours avec liens vers les événements
					if ($this->cevents)
					{
						// divs des événements
						if (isset($this->events[$jourStr]) && is_array($this->events[$jourStr]))
						{
							$nomdiv = "event_".parent::convert("UNX",$jourStr);
							$codeEvent .= "<div id=\"".$nomdiv."\" class=\"event\" style=\"display:none;\">";
							foreach ($this->events[$jourStr] as $event)
								$codeEvent .= $event['event'];
							$codeEvent .="</div>\n";
							$event = true;
						}
						//$onclick = $event ? " onmouseover=\"document.getElementById('".$nomdiv."').style.display='block';\" onmouseout=\"document.getElementById('".$nomdiv."').style.display='none';\"" : "";
					}
					
					
					// CODE HTML DES JOURS
					
					$codeJourInactif = "<td class=\"inactif\">".sprintf("%02d",$jour)."</td>\n";

					$codeJourActif = "<td class=\"actif\">";
					if ($this->liensJoursActif) 
						$codeJourActif .= "<a href=\"".$lienJour."\"".$this->targetLiens.">".sprintf("%02d",$jour)."</a>";
					else
						$codeJourActif .= sprintf("%02d",$jour);
					$codeJourActif .= "</td>\n";

					$codeJourActifToday = "<td class=\"today\">";
					if ($this->liensJoursActif) 
						$codeJourActifToday .= "<a href=\"".$lienJour."\"".$this->targetLiens.">".sprintf("%02d",$jour)."</a>";
					else
						$codeJourActifToday .= sprintf("%02d",$jour);
					$codeJourActifToday .= "</td>\n";
					
					// jours avec évenements
					$codeEvenement = "<td class=\"event\">";
					$codeEvenement .= "<a href=\"".$lienJour."\"".$onclick.$this->targetLiens.">".sprintf("%02d",$jour)."</a>";
					$codeEvenement .= "</td>\n";

					$codeEvenementToday = "<td class=\"today\">";
					$codeEvenementToday .= "<a href=\"".$lienJour."\"".$onclick.$this->targetLiens.">".sprintf("%02d",$jour)."</a>";
					$codeEvenementToday .= "</td>\n";
					
					$codeJourInactifToday = "<td class=\"today\">".sprintf("%02d",$jour)."</td>\n";
					
					
					// affichage jour passé
					if ($jourStr < date("Y-m-d") && $this->passe)
					{
						if ($this->dateMin && $jourStr < $this->convert("SQL",$this->dateMin))
							$code .= $codeJourInactif;
						else if (isset($this->jevent) && $event)
							$code .= $codeEvenement;
						else $code .= $codeJourActif;
					}
					// affichage jour futur
					else if ($jourStr > date("Y-m-d") && $this->futur)
					{
						if ($this->dateMax && $jourStr > $this->convert("SQL",$this->dateMax))
							$code .= $codeJourInactif;
						else if (isset($this->jevent) && $event)
							$code .= $codeEvenement;
						else $code .= $codeJourActif;
					}
					// affichage jour présent
					else if ($jourStr == date("Y-m-d") && $this->present)
					{
						if ( ($this->dateMax && $jourUnx > $this->dateMax) || ($this->dateMin && $jourUnx < $this->dateMin) )
							$code .= $codeJourInactifToday;
						else if (isset($this->jevent) && $event)
							$code .= $codeEvenementToday;
						else $code .= $codeJourActifToday;
					}
					// inactif par défaut
					else 
						$code .= $codeJourInactif;
					
				}
				else $code .= "<td>&nbsp;</td>";
				
				
				// fin de ligne
				if ($i%(7)==0)
				{
					$code .=  "</tr>\n";
					if ($i>=($max+$decal-1)) break;

					// no de semaine lignes suivantes
					if ($this->csemaines && $i<42)
					{
						$semaine = gmdate("Y-W",mktime(12,0,0,$a_mois,$jour+1,$a_annee));
						$noSemaine = gmdate("W",mktime(12,0,0,$a_mois,$jour+1,$a_annee));
						$curSemaine = gmdate("Y-W");
						$annee = $a_mois==12 && $semaine==1 ? $a_annee+1 : $a_annee;
						
						if (isset($this->formatLienSemaines))
							$lienSemaine = sprintf($this->formatLienSemaines,$a_annee,$semaine);
						else
							$lienSemaine = $this->_ecritLien($this->lienMois,$annee,0,0,$noSemaine);
						
						if (
						!$this->liensSemainesActif
						|| (isset($this->dateMin,$semaineMin) && $semaine < $semaineMin)
						|| (isset($this->dateMax,$semaineMax) && $semaine > $semaineMax)
						|| (!$this->futur && $semaine > $curSemaine) 
						|| (!$this->passe && $semaine < $curSemaine)
						)
							$code .=  "<tr><th>" . $noSemaine . "</th>";
						else
							$code .=  "<tr><th><a href=\"".$lienSemaine."\"".$this->targetLiens.">" . $noSemaine . "</a></th>";

					}
				}
			}
				$code .=  "<tr>\n";
				
				// lien mois précédent
				if(!$this->navigMois)			// pas de navigation au mois
					$code .= "";
				else if (
				($this->dateMin && $this->convert("SQL",$date) <= $this->convert("SQL",$this->dateMin))		// date inferieure à limite inf
				|| (!$this->passe && $this->convert("SQL",$date) <= gmdate("Y-m-d"))
				)
					$code .= "<th class=\"mois\">&nbsp;</th>";
				else
					$code .= "<th class=\"mois\"><a ". (strlen($lienMoisPrec)>0 ? "href=\"".$lienMoisPrec."\"" : "") .$onclickPrec.$this->targetNavig."><b><</b></a></th>";


				// lien mois en cours	
				$code .=  "<th colspan=\"".$colspan."\" class=\"mois\">".($this->lienMoisActif ? "<a href=\"".$lienMois."\"".$this->targetLiens.">" : "") . sprintf("%s %04d",$this->_num2Month($moisCur),$a_annee).($this->lienMoisActif ? "</a>" : "") . "</th>";
				
				

				// lien mois suivant				
				if(!$this->navigMois)			// pas de navigation au mois
					$code .= "";
				else if (
				($this->dateMax && $this->getDateFrom($max,"SQL",$date) >= $this->convert("SQL",$this->dateMax))		// date inferieure à limite inf
				|| (!$this->futur && $this->getDateFrom($max,"SQL",$date) >= gmdate("Y-m-d"))
				)
					$code .= "<th class=\"mois\">&nbsp;</th>";
				else
					$code .= "<th class=\"mois\"><a ". (strlen($lienMoisSuiv)>0 ? "href=\"".$lienMoisSuiv."\"" : "") .$onclickSuiv.$this->targetNavig."><b>></b></a></th>";

				$code .=  "</tr>\n";
			}
			$code .=  "</tr>\n";
			$code .=  "</table>\n";
			// affichage des événements
			if ($this->cevents)
			{
				$code .= "<div id=\"calendrier_events\">";
				$code .= $codeEvent;
				$code .= "</div>";
			}
			$code .=  "</div>";
			
		
			return $code;
		}





}	
	?>
