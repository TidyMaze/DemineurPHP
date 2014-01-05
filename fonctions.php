<?php
	/* réglages */
	define(COEFMINE, 8); /* 1 mine chaque COEFMINE cases */
	define(DEFAULTLIG,10);
	define(DEFAULTCOL,30);
	define(MAXLIG, 100);
	define(MAXCOL, 100);






	/*  constantes */
	define(MINLIG, 1);
	define(MINCOL, 1);

	/* tous les 8 offsets pour accéder aux cases voisines */
	$dir = array(array(-1,-1),array(-1,0),array(-1,1),array(0,-1),array(0,1),array(1,-1),array(1,0),array(1,1));

	/* génère un tableau de dimensions $lignes x $colonnes rempli de $val */
	function tab2D($lignes, $colonnes, $val){
		return array_fill(0, $lignes, array_fill(0, $colonnes, $val));
	}

	/* vérifie la conformité des coordonnées */
	function dansBornes($val, $min, $max){			return is_numeric($val) and $val >= $min and $val <= $max;}
	function dansPlateau($i, $j){					return dansBornes($i, 0,  $_SESSION['nblignes']-1) and dansBornes($j, 0,  $_SESSION['nbcolonnes']-1);}
	function tailleValide($nblignes, $nbcolonnes){	return dansBornes($nblignes, MINLIG, MAXLIG) and dansBornes($nbcolonnes,MINCOL,MAXCOL);}

	/* affiche une case en fonction de ses coordonnées (pour le lien) et de son état (pour l'image) */
	function affCase($i, $j){
		if($_SESSION['jeu'][$i][$j]){
			$chemin = "images/unknown.png";
			echo "<a href=\"?i=".$i."&amp;j=".$j."\"><img alt=\"case\" class=\"case\" src=\"".$chemin."\"></a>";
		} else {
			if($_SESSION['terrain'][$i][$j]){
				$chemin = "images/mine.png";
			} else {
				$valeur = $_SESSION['mapVoisins'][$i][$j];
				$chemin = "images/{$valeur}.png";
			}
			echo "<img alt=\"case\" class=\"case\" src=\"".$chemin."\">";
		}
	}

	/* affiche le plateau courant selon les valeurs des tableaux*/
	function affPlateau(){
		for($i=0;$i<$_SESSION['nblignes'];$i++){	
			echo "<tr>\n";
			for($j=0;$j<$_SESSION['nbcolonnes'];$j++){
				echo "	<td>";
				affCase($i, $j);
				echo "</td>\n";
			}
			echo "</tr>\n";
		}
	}

	/* génere le tableau Jeu */
	/* true : pas vu */
	/* false : vu */
	function genererJeu(){
		$_SESSION['jeu'] = tab2D($_SESSION['nblignes'],$_SESSION['nbcolonnes'],true);
	}

	/* parcourt les cases voisines et incrémente leur valeur de mines voisines */
	function ajouterVoisins($i, $j){
		global $dir;
		foreach($dir as $uneDir){
			$dstI = $i + $uneDir[0];
			$dstJ = $j + $uneDir[1];
			
			if(dansPlateau($dstI,$dstJ)) $_SESSION['mapVoisins'][$dstI][$dstJ]++;
		}
	}

	/* génère le tableau Terrain */
	/* true : miné */
	/* false : pas miné */
	function genererTerrain(){
		$_SESSION['terrain'] = tab2D($_SESSION['nblignes'],$_SESSION['nbcolonnes'],false);
		$_SESSION['mapVoisins'] = tab2D($_SESSION['nblignes'],$_SESSION['nbcolonnes'],0);
		for($k=0;$k<$_SESSION['nbmines'];$k++){
			do{
				$i = rand(0,$_SESSION['nblignes']-1);
				$j = rand(0,$_SESSION['nbcolonnes']-1);
			} while ($_SESSION['terrain'][$i][$j]);
			$_SESSION['terrain'][$i][$j] = true;
			ajouterVoisins($i, $j);
		}
	}

	/* met toutes les cases en visibles */
	function decouvrirJeu(){
		for($i=0;$i<$_SESSION['nblignes'];$i++){	
			for($j=0;$j<$_SESSION['nbcolonnes'];$j++){
				if($_SESSION['jeu'][$i][$j]){
					$_SESSION['jeu'][$i][$j] = false;
				}
			}
		}
	}

	/* permet de créer la map et les variables de jeu, stockage par session */
	function initPartie($nblignes,$nbcolonnes){
			session_destroy();	// par sécurité
			session_start();	// pour pouvoir gérer la suite du programme
			$_SESSION['nblignes'] = $nblignes;
			$_SESSION['nbcolonnes'] = $nbcolonnes;
			$_SESSION['nbmines'] = calculeNbMines($_SESSION['nblignes'],$_SESSION['nbcolonnes']);
			genererJeu();
			genererTerrain();
			$_SESSION['genererMap'] = false;
			$_SESSION['GAGNE']		= false;
			$_SESSION['PERDU']		= false;
	}
	
	/* affiche les informations de la partie */
	function affInfo(){
		if($_SESSION['GAGNE'] && !$_SESSION['PERDU']){
			echo "<p>Vous avez gagné !</p>\n";
		} else if ($_SESSION['PERDU'] && !$_SESSION['GAGNE']){
			echo "<p>Vous avez perdu ...</p>\n";
		} else  if($_SESSION['PERDU'] && $_SESSION['GAGNE']){
			echo "<p>Trop de café ... tue le café</p>\n";
		}
	}

	/* étend le trou causé par un clic sur une case vide et ne touchant pas une mine */
	function propagerTrou($i, $j){
		
		$_SESSION['jeu'][$i][$j] = false;
		
		if($_SESSION['mapVoisins'][$i][$j] == 0){
			global $dir;
			foreach($dir as $uneDir){
				$dstI = $i + $uneDir[0];
				$dstJ = $j + $uneDir[1];
				
				if(dansPlateau($i,$j) and $_SESSION['jeu'][$dstI][$dstJ]){
					propagerTrou($dstI,$dstJ);
				}
			}
		}
	}
	
	/* vérifie s'il reste des cases non minées et non vues */
	function restePasMine(){
		for($i=0;$i<$_SESSION['nblignes'];$i++){	
			for($j=0;$j<$_SESSION['nbcolonnes'];$j++){
				if($_SESSION['jeu'][$i][$j] && !$_SESSION['terrain'][$i][$j]){
					return true;
				}
			}
		}
		return false;
	}

	/* gère un clic sur le plateau de jeu, si possible */
	function handleClick(){
	
		/* informe du clic sur la case */
		if(!$_SESSION['GAGNE'] and !$_SESSION['PERDU'] and isset($_GET['i']) and isset($_GET['j'])){
		
			$i = $_GET['i'];
			$j = $_GET['j'];
			
			/* vérifie les coordonnées, une action est faite que si la case n'était pas vue */
			if(dansPlateau($i,$j) and $_SESSION['jeu'][$i][$j]){
			
				$_SESSION['jeu'][$i][$j] = false;
				
				/* vérifie si la case contient une mine */
				if($_SESSION['terrain'][$i][$j]){
				
					$_SESSION['PERDU'] = true;
					decouvrirJeu();
					
				} else {
					if($_SESSION['mapVoisins'][$i][$j] == 0){
						propagerTrou($i, $j);
					}
					
					if(!restePasMine()){
						$_SESSION['GAGNE'] = true;
					}
				}
			}
		}
	}
	
	/* calcule le nombre de mines à ajouter */
	function calculeNbMines($nblignes, $nbcolonnes){
		if(COEFMINE > 0){
			return $nblignes * $nbcolonnes / COEFMINE;
		} else {
			echo "erreur, coefficient de mines : doit être positif";
		}
	}
	
	/* gère une demande de reset/nouvelle partie, si c'est le cas */
	function handleReset(){
		/* premier lancement du jeu : valeurs par défaut */
		if(!isset($_SESSION['genererMap'])){
			initPartie(DEFAULTLIG,DEFAULTCOL);
		
		/* autre redémarrage (suite au bouton nouvelle partie) */
		} else if(isset($_GET['nblignes']) and isset($_GET['nbcolonnes']) and tailleValide($_GET['nblignes'],$_GET['nbcolonnes'])){
			initPartie($_GET['nblignes'],$_GET['nbcolonnes']);
		}	
	}
?>