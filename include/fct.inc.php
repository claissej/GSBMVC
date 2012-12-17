<?php
/** 
 * Fonctions pour l'application GSB
 
 * @package default
 * @author Cheri Bibi
 * @version    1.0
 */
 /**
 * Teste si un quelconque visiteur est connecté
 * @return vrai ou faux 
 */
function estConnecte(){
  return isset($_SESSION['idVisiteur']);
}
/**
 * Enregistre dans une variable session les infos d'un visiteur
 
 * @param $id 
 * @param $nom
 * @param $prenom
 */
function connecter($id,$nom,$prenom, $type){
	$_SESSION['idVisiteur']= $id; 
	$_SESSION['nom']= $nom;
	$_SESSION['prenom']= $prenom;
	$_SESSION['type']= $type;
}
/**
 * Détruit la session active
 */
function deconnecter(){
        foreach ($_SESSION as $key => $value) {
                unset($_SESSION[$key]);
        }
        session_destroy();
}
/**
 * Transforme une date au format français jj/mm/aaaa vers le format anglais aaaa-mm-jj
 
 * @param $madate au format  jj/mm/aaaa
 * @return la date au format anglais aaaa-mm-jj
*/
function dateFrancaisVersAnglais($maDate){
	@list($jour,$mois,$annee) = explode('/',$maDate);
	return date('Y-m-d',mktime(0,0,0,$mois,$jour,$annee));
}
/**
 * Transforme une date au format format anglais aaaa-mm-jj vers le format français jj/mm/aaaa 
 
 * @param $madate au format  aaaa-mm-jj
 * @return la date au format format français jj/mm/aaaa
*/
function dateAnglaisVersFrancais($maDate){
   @list($annee,$mois,$jour)=explode('-',$maDate);
   $date="$jour"."/".$mois."/".$annee;
   return $date;
}
/**
 * retourne le mois au format aaaamm selon le jour dans le mois
 
 * @param $date au format  jj/mm/aaaa
 * @return le mois au format aaaamm
*/
function getMois($date){
		@list($jour,$mois,$annee) = explode('/',$date);
		if(strlen($mois) == 1){
			$mois = "0".$mois;
		}
		return $annee.$mois;
}

/* gestion des erreurs*/
/**
 * Indique si une valeur est un entier positif ou nul
 
 * @param $valeur
 * @return vrai ou faux
*/
function estEntierPositif($valeur) {
	return preg_match("/[^0-9]/", $valeur) == 0;
	
}

/**
 * Indique si un tableau de valeurs est constitué d'entiers positifs ou nuls
 
 * @param $tabEntiers : le tableau
 * @return vrai ou faux
*/
function estTableauEntiers($tabEntiers) {
	$ok = true;
	foreach($tabEntiers as $unEntier){
		if(!estEntierPositif($unEntier)){
		 	$ok=false; 
		}
	}
	return $ok;
}
/**
 * Vérifie si une date est inférieure d'un an à la date actuelle
 
 * @param $dateTestee 
 * @return vrai ou faux
*/
function estDateDepassee($dateTestee){
	$dateActuelle=date("d/m/Y");
	@list($jour,$mois,$annee) = explode('/',$dateActuelle);
	$annee--;
	$AnPasse = $annee.$mois.$jour;
	@list($jourTeste,$moisTeste,$anneeTeste) = explode('/',$dateTestee);
	return ($anneeTeste.$moisTeste.$jourTeste < $AnPasse); 
}
/**
 * Vérifie la validité du format d'une date française jj/mm/aaaa 
 
 * @param $date 
 * @return vrai ou faux
*/
function estDateValide($date){
	$tabDate = explode('/',$date);
	$dateOK = true;
	if (count($tabDate) != 3) {
	    $dateOK = false;
    }
    else {
		if (!estTableauEntiers($tabDate)) {
			$dateOK = false;
		}
		else {
			if (!checkdate($tabDate[1], $tabDate[0], $tabDate[2])) {
				$dateOK = false;
			}
		}
    }
	return $dateOK;
}

/**
 * Vérifie que le tableau de frais ne contient que des valeurs numériques 
 
 * @param $lesFrais 
 * @return vrai ou faux
*/
function lesQteFraisValides($lesFrais){
	return estTableauEntiers($lesFrais);
}
/**
 * Vérifie la validité des trois arguments : la date, le libellé du frais et le montant 
 
 * des message d'erreurs sont ajoutés au tableau des erreurs
 
 * @param $dateFrais 
 * @param $libelle 
 * @param $montant
 */
function valideInfosFrais($dateFrais,$libelle,$montant){
	if($dateFrais==""){
		ajouterErreur("Le champ date ne doit pas être vide");
	}
	else{
		if(!estDatevalide($dateFrais)){
			ajouterErreur("Date invalide");
		}	
		else{
			if(estDateDepassee($dateFrais)){
				ajouterErreur("date d'enregistrement du frais dépassé, plus de 1 an");
			}			
		}
	}
	if($libelle == ""){
		ajouterErreur("Le champ description ne peut pas être vide");
	}
	if($montant == ""){
		ajouterErreur("Le champ montant ne peut pas être vide");
	}
	else
		if( !is_numeric($montant) ){
			ajouterErreur("Le champ montant doit être numérique");
		}
}
/**
 * Ajoute le libellé d'une erreur au tableau des erreurs 
 
 * @param $msg : le libellé de l'erreur 
 */
function ajouterErreur($msg){
   if (! isset($_REQUEST['erreurs'])){
      $_REQUEST['erreurs']=array();
	} 
   $_REQUEST['erreurs'][]=$msg;
}
/**
 * Retoune le nombre de lignes du tableau des erreurs 
 
 * @return le nombre d'erreurs
 */
function nbErreurs(){
   if (!isset($_REQUEST['erreurs'])){
	   return 0;
	}
	else{
	   return count($_REQUEST['erreurs']);
	}
}

/**
 * Retoune une chaine de caractere avec les filtre pour la base de donnée 
 
 * @param $str : chaine de caractere 
 * @return la chaine de caractere avec les filtre pour la base de donnée 
 */
  function filtrerChainePourBD($str) {
    if ( ! get_magic_quotes_gpc() ) { 
        // si la directive de configuration magic_quotes_gpc est activée dans php.ini,
        // toute chaîne reçue par get, post ou cookie est déjà échappée 
        // par conséquent, il ne faut pas échapper la chaîne une seconde fois                              
        $str = mysql_real_escape_string($str);
    }
    return $str;
}  
/**
 * Retoune la valeur du poste 
 
 * @param $nomDonne : nom de l'index
 * @param $valDefaut : val de retour par defaut si l'index n'existe pas (non obligatoire)
 * @return la valeur du post en fonction de l'index
 */
function lireDonneePost($nomDonnee, $valDefaut="") {
    if ( isset($_POST[$nomDonnee]) ) {
        $val = $_POST[$nomDonnee];
    }
    else {
        $val = $valDefaut;
    }
    return $val;
}

/**
 * Retoune la requete sql pour les lignes de frais en fonction d'un mois et d'un visiteur
 
 * @param $unMois : le mois
 * @param $unIdVisiteur : id du visiteur
 * @return chaine de caractere composant la requete sql
 */
function obtenirReqEltsHorsForfaitFicheFrais($unMois, $unIdVisiteur) {
    $unMois = filtrerChainePourBD($unMois);
    $requete = "select id, date, libelle, montant from LigneFraisHorsForfait
              where idVisiteur='" . $unIdVisiteur 
              . "' and mois='" . $unMois . "'";
    return $requete;
}
/**
 * Retoune le mois en lettre en fonction de son numéro (1: janvier, 2: fevrier, ...)
 
 * @param $unMois : le numero du mois (1: janvier)
 * @return chaine de caractere etant le mois en toutes lettres
 */
function obtenirLibelleMois($unNoMois) {
    $tabLibelles = array(1=>"Janvier", 
                            "Février", "Mars", "Avril", "Mai", "Juin", "Juillet",
                            "Août", "Septembre", "Octobre", "Novembre", "Décembre");
    $libelle="";
    if ( $unNoMois >=1 && $unNoMois <= 12 ) {
        $libelle = $tabLibelles[$unNoMois];
    }
    return $libelle;
}
/**
 * Retoune une chaine de caractere avec les caracteres spéciaux HTML
 
 * @param $str : chaine de caractere destiné à être affichée
 * @return chaine de caractere avec les caracteres spéciaux HTML
 */
function filtrerChainePourNavig($str) {
    return htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
}

/**
 * Retoune une chaine de caractere contenant les erreurs si elles existent
 
 * @return chaine de caractere avec les erreurs
 */
function toStringErreurs() {
    $tabErr = $_REQUEST['erreurs'];
    $str = '<div class="erreur">';
    $str .= '<ul>';
    foreach($tabErr as $erreur){
        $str .= '<li>' . $erreur . '</li>';
	}
    $str .= '</ul>';
    $str .= '</div>';
    return $str;
} 
?>