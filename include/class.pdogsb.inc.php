<?php
/** 
 * Classe d'accès aux données. 
 
 * Utilise les services de la classe PDO
 * pour l'application GSB
 * Les attributs sont tous statiques,
 * les 4 premiers pour la connexion
 * $monPdo de type PDO 
 * $monPdoGsb qui contiendra l'unique instance de la classe
 
 * @package default
 * @author Cheri Bibi
 * @version    1.0
 * @link       http://www.php.net/manual/fr/book.pdo.php
 */

class PdoGsb{   		
      	private static $serveur='mysql:host=localhost';
      	private static $bdd='dbname=gsbmvc_claisse';   		
      	private static $user='root' ;    		
      	private static $mdp='' ;	
		private static $monPdo;
		private static $monPdoGsb=null;
/**
 * Constructeur privé, crée l'instance de PDO qui sera sollicitée
 * pour toutes les méthodes de la classe
 */				
	private function __construct(){
    	PdoGsb::$monPdo = new PDO(PdoGsb::$serveur.';'.PdoGsb::$bdd, PdoGsb::$user, PdoGsb::$mdp); 
		PdoGsb::$monPdo->query("SET CHARACTER SET utf8");
	}
	public function _destruct(){
		PdoGsb::$monPdo = null;
	}
/**
 * Fonction statique qui crée l'unique instance de la classe
 
 * Appel : $instancePdoGsb = PdoGsb::getPdoGsb();
 
 * @return l'unique objet de la classe PdoGsb
 */
	public  static function getPdoGsb(){
		if(PdoGsb::$monPdoGsb==null){
			PdoGsb::$monPdoGsb= new PdoGsb();
		}
		return PdoGsb::$monPdoGsb;  
	}
/**
 * Retourne les informations d'un visiteur
 
 * @param $login 
 * @param $mdp
 * @return l'id, le nom et le prénom sous la forme d'un tableau associatif 
*/
	public function getInfosVisiteur($login, $mdp){
		$req = "select type, login from utilisateur 
		where login='$login' and mdp='$mdp'";
		$rs = PdoGsb::$monPdo->query($req);
		$ligne = $rs->fetch();
                $type = $ligne['type'];
                if($ligne['type'] == "V")
                    $req = "select visiteur.id as id, visiteur.nom as nom, visiteur.prenom as prenom from visiteur where visiteur.login='$login'";
                else if($ligne['type'] == "C")
                    $req = "select gestionnaire.id as id, gestionnaire.nom as nom, gestionnaire.prenom as prenom from gestionnaire where gestionnaire.login='$login'";
                
		$rs = PdoGsb::$monPdo->query($req);
		$ligne = $rs->fetch();
                $ligne['type'] = $type;
                if(isset($ligne['id']))
                    return $ligne;
                else
                    return null;
        }

/**
 * Retourne sous forme d'un tableau associatif toutes les lignes de frais hors forfait
 * concernées par les deux arguments
 
 * La boucle foreach ne peut être utilisée ici car on procède
 * à une modification de la structure itérée - transformation du champ date-
 
 * @param $idVisiteur 
 * @param $mois sous la forme aaaamm
 * @return tous les champs des lignes de frais hors forfait sous la forme d'un tableau associatif 
*/
	public function getLesFraisHorsForfait($idVisiteur,$mois){
	    $req = "select * from lignefraishorsforfait where lignefraishorsforfait.idvisiteur ='$idVisiteur' 
		and lignefraishorsforfait.mois = '$mois' ";	
		$res = PdoGsb::$monPdo->query($req);
		$lesLignes = $res->fetchAll();
		$nbLignes = count($lesLignes);
		for ($i=0; $i<$nbLignes; $i++){
			$date = $lesLignes[$i]['date'];
			$lesLignes[$i]['date'] =  dateAnglaisVersFrancais($date);
		}
		return $lesLignes; 
	}
/**
 * Retourne le nombre de justificatif d'un visiteur pour un mois donné
 
 * @param $idVisiteur 
 * @param $mois sous la forme aaaamm
 * @return le nombre entier de justificatifs 
*/
	public function getNbjustificatifs($idVisiteur, $mois){
		$req = "select fichefrais.nbjustificatifs as nb from  fichefrais where fichefrais.idvisiteur ='$idVisiteur' and fichefrais.mois = '$mois'";
		$res = PdoGsb::$monPdo->query($req);
		$laLigne = $res->fetch();
		return $laLigne['nb'];
	}
/**
 * Retourne sous forme d'un tableau associatif toutes les lignes de frais au forfait
 * concernées par les deux arguments
 
 * @param $idVisiteur 
 * @param $mois sous la forme aaaamm
 * @return l'id, le libelle et la quantité sous la forme d'un tableau associatif 
*/
	public function getLesFraisForfait($idVisiteur, $mois){
		$req = "select fraisforfait.id as idfrais, fraisforfait.libelle as libelle, 
		lignefraisforfait.quantite as quantite from lignefraisforfait inner join fraisforfait 
		on fraisforfait.id = lignefraisforfait.idfraisforfait
		where lignefraisforfait.idvisiteur ='$idVisiteur' and lignefraisforfait.mois='$mois' 
		order by lignefraisforfait.idfraisforfait";	
		$res = PdoGsb::$monPdo->query($req);
		$lesLignes = $res->fetchAll();
		return $lesLignes; 
	}
/**
 * Retourne tous les id de la table FraisForfait
 
 * @return un tableau associatif 
*/
	public function getLesIdFrais(){
		$req = "select fraisforfait.id as idfrais from fraisforfait order by fraisforfait.id";
		$res = PdoGsb::$monPdo->query($req);
		$lesLignes = $res->fetchAll();
		return $lesLignes;
	}
/**
 * Met à jour la table ligneFraisForfait
 
 * Met à jour la table ligneFraisForfait pour un visiteur et
 * un mois donné en enregistrant les nouveaux montants
 
 * @param $idVisiteur 
 * @param $mois sous la forme aaaamm
 * @param $lesFrais tableau associatif de clé idFrais et de valeur la quantité pour ce frais
 * @return un tableau associatif 
*/
	public function majFraisForfait($idVisiteur, $mois, $lesFrais){
		$lesCles = array_keys($lesFrais);
		foreach($lesCles as $unIdFrais){
			$qte = $lesFrais[$unIdFrais];
			$req = "update lignefraisforfait set lignefraisforfait.quantite = $qte
			where lignefraisforfait.idvisiteur = '$idVisiteur' and lignefraisforfait.mois = '$mois'
			and lignefraisforfait.idfraisforfait = '$unIdFrais'";
			PdoGsb::$monPdo->exec($req);
		}
		
	}
/**
 * met à jour le nombre de justificatifs de la table ficheFrais
 * pour le mois et le visiteur concerné
 
 * @param $idVisiteur 
 * @param $mois sous la forme aaaamm
*/
	public function majNbJustificatifs($idVisiteur, $mois, $nbJustificatifs){
		$req = "update fichefrais set nbjustificatifs = $nbJustificatifs 
		where fichefrais.idvisiteur = '$idVisiteur' and fichefrais.mois = '$mois'";
		PdoGsb::$monPdo->exec($req);	
	}
/**
 * Teste si un visiteur possède une fiche de frais pour le mois passé en argument
 
 * @param $idVisiteur 
 * @param $mois sous la forme aaaamm
 * @return vrai ou faux 
*/	
	public function estPremierFraisMois($idVisiteur,$mois)
	{
		$ok = false;
		$req = "select count(*) as nblignesfrais from fichefrais 
		where fichefrais.mois = '$mois' and fichefrais.idvisiteur = '$idVisiteur'";
		$res = PdoGsb::$monPdo->query($req);
		$laLigne = $res->fetch();
		if($laLigne['nblignesfrais'] == 0){
			$ok = true;
		}
		return $ok;
	}
/**
 * Retourne le dernier mois en cours d'un visiteur
 
 * @param $idVisiteur 
 * @return le mois sous la forme aaaamm
*/	
	public function dernierMoisSaisi($idVisiteur){
		$req = "select max(mois) as dernierMois from fichefrais where fichefrais.idvisiteur = '$idVisiteur'";
		$res = PdoGsb::$monPdo->query($req);
		$laLigne = $res->fetch();
		$dernierMois = $laLigne['dernierMois'];
		return $dernierMois;
	}
	
/**
 * Crée une nouvelle fiche de frais et les lignes de frais au forfait pour un visiteur et un mois donnés
 
 * récupère le dernier mois en cours de traitement, met à 'CL' son champs idEtat, crée une nouvelle fiche de frais
 * avec un idEtat à 'CR' et crée les lignes de frais forfait de quantités nulles 
 * @param $idVisiteur 
 * @param $mois sous la forme aaaamm
*/
	public function creeNouvellesLignesFrais($idVisiteur,$mois){
		$dernierMois = $this->dernierMoisSaisi($idVisiteur);
		$laDerniereFiche = $this->getLesInfosFicheFrais($idVisiteur,$dernierMois);
		if($laDerniereFiche['idEtat']=='CR'){
				$this->majEtatFicheFrais($idVisiteur, $dernierMois,'CL');
				
		}
		$req = "insert into fichefrais(idvisiteur,mois,nbJustificatifs,montantValide,dateModif,idEtat) 
		values('$idVisiteur','$mois',0,0,now(),'CR')";
		PdoGsb::$monPdo->exec($req);
		$lesIdFrais = $this->getLesIdFrais();
		foreach($lesIdFrais as $uneLigneIdFrais){
			$unIdFrais = $uneLigneIdFrais['idfrais'];
			$req = "insert into lignefraisforfait(idvisiteur,mois,idFraisForfait,quantite) 
			values('$idVisiteur','$mois','$unIdFrais',0)";
			PdoGsb::$monPdo->exec($req);
		 }
	}
/**
 * Crée un nouveau frais hors forfait pour un visiteur un mois donné
 * à partir des informations fournies en paramètre
 
 * @param $idVisiteur 
 * @param $mois sous la forme aaaamm
 * @param $libelle : le libelle du frais
 * @param $date : la date du frais au format français jj//mm/aaaa
 * @param $montant : le montant
*/
	public function creeNouveauFraisHorsForfait($idVisiteur,$mois,$libelle,$date,$montant){
		$dateFr = dateFrancaisVersAnglais($date);
		$req = "insert into lignefraishorsforfait 
		values('','$idVisiteur','$mois','$libelle','$dateFr','$montant')";
		PdoGsb::$monPdo->exec($req);
	}
/**
 * Supprime le frais hors forfait dont l'id est passé en argument
 
 * @param $idFrais 
*/
	public function supprimerFraisHorsForfait($idFrais){
		$req = "delete from lignefraishorsforfait where lignefraishorsforfait.id =$idFrais ";
		PdoGsb::$monPdo->exec($req);
	}
/**
 * Retourne les mois pour lesquel un visiteur a une fiche de frais
 
 * @param $idVisiteur 
 * @return un tableau associatif de clé un mois -aaaamm- et de valeurs l'année et le mois correspondant 
*/
	public function getLesMoisDisponibles($idVisiteur){
		$req = "select fichefrais.mois as mois from  fichefrais where fichefrais.idvisiteur ='$idVisiteur' 
		order by fichefrais.mois desc ";
		$res = PdoGsb::$monPdo->query($req);
		$lesMois =array();
		$laLigne = $res->fetch();
		while($laLigne != null)	{
			$mois = $laLigne['mois'];
			$numAnnee =substr( $mois,0,4);
			$numMois =substr( $mois,4,2);
			$lesMois["$mois"]=array(
		     "mois"=>"$mois",
		    "numAnnee"  => "$numAnnee",
			"numMois"  => "$numMois"
             );
			$laLigne = $res->fetch(); 		
		}
		return $lesMois;
	}
/**
 * Retourne les informations d'une fiche de frais d'un visiteur pour un mois donné
 
 * @param $idVisiteur 
 * @param $mois sous la forme aaaamm
 * @return un tableau avec des champs de jointure entre une fiche de frais et la ligne d'état 
*/	
	public function getLesInfosFicheFrais($idVisiteur,$mois){
		$req = "select ficheFrais.idEtat as idEtat, ficheFrais.dateModif as dateModif, ficheFrais.nbJustificatifs as nbJustificatifs, 
			ficheFrais.montantValide as montantValide, etat.libelle as libEtat from  fichefrais inner join Etat on ficheFrais.idEtat = Etat.id 
			where fichefrais.idvisiteur ='$idVisiteur' and fichefrais.mois = '$mois'";
		$res = PdoGsb::$monPdo->query($req);
		$laLigne = $res->fetch();
		return $laLigne;
	}
/**
 * Modifie l'état et la date de modification d'une fiche de frais
 
 * Modifie le champ idEtat et met la date de modif à aujourd'hui
 * @param $idVisiteur 
 * @param $mois sous la forme aaaamm
 */
 
	public function majEtatFicheFrais($idVisiteur,$mois,$etat){
		$req = "update ficheFrais set idEtat = '$etat', dateModif = now() 
		where fichefrais.idvisiteur ='$idVisiteur' and fichefrais.mois = '$mois'";
		PdoGsb::$monPdo->exec($req);
	}


function visiteurFicheEnCours() {
	$requete = "Select DISTINCT(id),nom,prenom from visiteur Inner join fichefrais on fichefrais.idVisiteur = visiteur.id Where fichefrais.idEtat='CR'";
	return PdoGsb::$monPdo->query($requete);
}

function moisFicheEnCours() {
	$requete = " Select DISTINCT(mois) from fichefrais Where fichefrais.idEtat='CR'";
	return PdoGsb::$monPdo->query($requete);
}
function gg(){
$req1="Select nom from visiteur Inner join fichefrais on fichefrais.idVisiteur = visiteur.id where id='".$_POST['Id']."' and mois='".$_POST['mois']."'";
return PdoGsb::$monPdo->query($req1);
}

function obtenirDetailFicheFrais($unMois, $unIdVisiteur) {
    $unMois = filtrerChainePourBD($unMois);
    $ligne = false;
    $requete="select IFNULL(nbJustificatifs,0) as nbJustificatifs, Etat.id as idEtat, libelle as libelleEtat, dateModif, montantValide 
    from FicheFrais inner join Etat on idEtat = Etat.id 
    where idVisiteur='" . $unIdVisiteur . "' and mois='" . $unMois . "'";
    $idJeuRes = PdoGsb::$monPdo->query($requete);  
    if ( $idJeuRes ) {
        $ligne = $idJeuRes->fetch();
    }        

    return $ligne ;
}
function existeFicheFrais( $unMois, $unIdVisiteur) {
    $unMois = filtrerChainePourBD($unMois);
    $requete = "select idVisiteur from FicheFrais where idVisiteur='" . $unIdVisiteur . 
              "' and mois='" . $unMois . "'";
    $idJeuRes = PdoGsb::$monPdo->query($requete);  
    if($idJeuRes->rowCount() == 1)
        return true;
    else
        return false;
}
function obtenirTableauFicheFrais($user, $mois)
{
     $requete="select idFraisForfait ,quantite from lignefraisforfait WHERE idVisiteur = \"".$user."\" and mois='" . $mois . "'";
     $query = PdoGsb::$monPdo->query($requete) ;
     $retour = Array();
     $i = 0;
     while($data = $query->fetch())
     {
         $retour[$i] = $data;
         $i++;
     }
     return $retour;
}
function obtenirTableauMontantFrais()
{
     $requete = "select id ,montant from fraisforfait";
     $query = PdoGsb::$monPdo->query($requete) ;
     $retour = Array();
     while($data = $query->fetch())
     {
         $retour[$data['id']] = $data['montant'];
     }
     return $retour;
}

function obtenirReqMoisFrais() {
    $req = "select distinct(fichefrais.mois) as mois from  fichefrais where idEtat='VA'";
    return $req ;
}
function obtenirReqFraisVisiteur()
{   
    $reqvisiteur = "select distinct (idVisiteur) from fichefrais where idEtat='VA'";
    return $reqvisiteur;
} 
function obtenirReqNbFicheFrais($unMois, $unIdVisiteur) {
    
    $unMois = filtrerChainePourBD($unMois);
    $requete = "SELECT fraisforfait.libelle, sum(quantite) as quantite
    FROM lignefraisforfait INNER JOIN fichefrais 
    ON lignefraisforfait.idVisiteur = fichefrais.idVisiteur
    INNER JOIN fraisforfait
    ON fraisforfait.id = lignefraisforfait.idFraisForfait
    WHERE fichefrais.idEtat = \"VA\" 
    and fichefrais.idVisiteur=\"".$unIdVisiteur."\" 
    and fichefrais.mois=\"".$unMois."\" 
    group by idFraisForfait";
    return $requete;
}

public function obtenirMoisFrais()
{
     $requete = "select distinct(fichefrais.mois) as mois from  fichefrais where idEtat='VA'";
     $query = PdoGsb::$monPdo->query($requete);
     $retour = Array();
     $i = 0;
     while($data = $query->fetch())
     {
         $retour[$i] = $data['mois'];
         $i++;
     }
     return $retour;
}
public function obtenirFraisVisiteur()
{
     $requete = "select distinct (idVisiteur) as visiteur from fichefrais where idEtat='VA'";
     $query = PdoGsb::$monPdo->query($requete);
     $retour = Array();
     $i = 0;
     while($data = $query->fetch())
     {
         $retour[$i] = $data['visiteur'];
         $i++;
     }
     return $retour;
}

public function obtenirMontantTotal($userSaisi, $moisSaisi)
{   
     
     $requete = "select SUM(quantite*montant) AS total
                    from lignefraisforfait INNER JOIN fraisforfait
                    ON lignefraisforfait.idFraisForfait = fraisforfait.id
                    WHERE idVisiteur = '".$userSaisi."' and mois= '".$moisSaisi."'";
     $query = PdoGsb::$monPdo->query($requete);
     $data = $query->fetch();
     return $data['total'];
}

public function obtenirTabEltsFraisForfait($userSaisi, $moisSaisi)
{   
    $requete = "SELECT fraisforfait.libelle, sum(quantite) as quantite
                FROM lignefraisforfait INNER JOIN fichefrais 
                ON lignefraisforfait.idVisiteur = fichefrais.idVisiteur
                INNER JOIN fraisforfait
                ON fraisforfait.id = lignefraisforfait.idFraisForfait
                WHERE fichefrais.idEtat = \"VA\" 
                and fichefrais.idVisiteur=\"".$userSaisi."\" 
                and fichefrais.mois=\"".$moisSaisi."\" 
                group by idFraisForfait";
    $idJeuEltsFraisForfait = PdoGsb::$monPdo->query($requete);
    $tabEltsFraisForfait = array();
    while ( $lgEltForfait = $idJeuEltsFraisForfait->fetch()) 
    {
        $tabEltsFraisForfait[$lgEltForfait["libelle"]] = $lgEltForfait["quantite"];
    }
    return $tabEltsFraisForfait;
}

public function obtenirLgEltHorsForfait($userSaisi, $moisSaisi)
{
    $requete = "select id, date, libelle, montant from LigneFraisHorsForfait
              where idVisiteur='" . $userSaisi 
              . "' and mois='" . $moisSaisi . "'";
    $query = PdoGsb::$monPdo->query($requete);
    
     $retour = Array();
     $i = 0;
     while($data = $query->fetch())
     {
         $retour[$i] = $data;
         $i++;
     }
     return $retour;
}
}
?>