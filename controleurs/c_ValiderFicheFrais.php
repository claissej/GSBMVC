﻿<?php

/** 
 * Page d'accueil de l'application web AppliFrais
 * @package default
 * @todo  RAS
 */
  $repInclude = './include/';
  require($repInclude . "_init.inc.php");

  // page inaccessible si visiteur non connecté
  if ( ! estVisiteurConnecte() ) 
  {
        header("Location: cSeConnecter.php");  
  }
  require($repInclude . "_entete.inc.html");
  require($repInclude . "_sommaire.inc.php");
  $listeVisiteur = visiteurFicheEnCours();
  $listeMois = 	moisFicheEnCours();
  $i=0;
  
  if(isset($_POST['bool']))
	{
		$Id=$_POST['Id'];
		$Mois=$_POST['mois'];
		$req1="Select nom from visiteur Inner join fichefrais on fichefrais.idVisiteur = visiteur.id where id='".$_POST['Id']."' and mois='".$_POST['mois']."'";
		$res=mysql_query($req1);
		if(mysql_num_rows($res)!=0)
		{
			$VisiteurMoisValide="Vrai";
			
		}
		else
		{
			$VisiteurMoisValide="Faux";
		}
		
			
	}

?>

   <!-- Division principale -->
  <div id="contenu">
      <h2>Valider les fiches de frais des visiteurs médicaux</h2>
	    <div class="corpsForm">
		<fieldset>
            <legend>Visiteur à sélectionner :</legend>
            
                <form action="" method="post">
				Visiteur :
		<select name="Id">
		
		<?php
			while($visiteur = mysql_fetch_array($listeVisiteur))
			{
				echo"
				<option label='Visiteur' value='".$visiteur['id']."'>".$visiteur['nom']." ".$visiteur['prenom']."</option>
				";
				
			}
			echo"</select><br> Mois : <select name='mois'>";
			
			while($mois=mysql_fetch_array($listeMois))
			{
				echo"
				<option label='MoisVisiteurs' value='".$mois['mois']."'>".substr($mois['mois'],4,5)."/".substr($mois['mois'],0,4)."</option>
				";
				
			}
			?>  </select>
			</div>
			<p align="left">
			<input type="Submit" value="Valider"> 
			</p>
			<?php
			  if(isset($_POST['bool']))
				{
				if($VisiteurMoisValide=="Faux")
					{
						echo"<br> Pas de fiche de frais pour ce visiteur ce mois ou fiche de frais déjà validée.";
					
					}
					elseif($VisiteurMoisValide=="Vrai")
					{	
						$res=obtenirDetailFicheFrais($idConnexion, $Mois, $Id);
						echo"<table cellpadding='5'>";
						echo"<tr><td >Justificatifs : </td><td> ".$res['nbJustificatifs']."</td></tr>";
						echo"<tr><td >Etat : </td><td> ".$res['libelleEtat']."</td></tr>";
						echo"<tr><td >Date de modification : </td><td> ".$res['dateModif']."</td></tr>";
						echo"<tr><td >Montant valider : </td><td> ".$res['montantValide']."</td></tr>";

						echo"</table>";
					
					}
				}
			?>
                   <INPUT TYPE='hidden' NAME='bool' VALUE='True'>
               </div>     
                </form>
		</fieldset>
    </div>
<?php        
  require($repInclude . "_pied.inc.html");
  require($repInclude . "_fin.inc.php");
?>