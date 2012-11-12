
<?php
$repInclude = './include/';
  require($repInclude . "_init.inc.php");

  // page inaccessible si visiteur non connecté
  if ( ! estVisiteurConnecte() ) 
      {
      header("Location: cSeConnecter.php");  
      }
      
      require($repInclude . "_entete.inc.html");  
      require($repInclude . "_sommaire.inc.php");
  
  
  // acquisition des données entrées, ici le numéro de mois et l'étape du traitement
  //verifie si les donnéesrentrer existe ou pas sinon retourne une valeur par default
  $moisSaisi=lireDonneePost("lstMois", "");
  $etape=lireDonneePost("etape",""); 
  $userSaisi=lireDonneePost("lstVisiteur","");
  
  $_SESSION['mois']=$moisSaisi;
  $_SESSION['user']=$userSaisi;
  
  if ($etape != "demanderConsult" && $etape != "validerConsult") 
      {
      // si autre valeur, on considère que c'est le début du traitement
      $etape = "demanderConsult";        
      } 
  if ($etape == "validerConsult") 
      { // l'utilisateur valide ses nouvelles données
                
      // vérification de l'existence de la fiche de frais pour le mois demandé
      $existeFicheFrais = existeFicheFrais($idConnexion, $moisSaisi, $userSaisi);
      // si elle n'existe pas, on la crée avec les élements frais forfaitisés à 0
          if ( !$existeFicheFrais ) 
          {
              ajouterErreur($tabErreurs, "Le mois demandé est invalide");
          }
          else 
              {
              // récupération des données sur la fiche de frais demandée
              $tabFicheFrais = obtenirDetailFicheFrais($idConnexion, $moisSaisi, $userSaisi);
              $tabFirst = obtenirTableauFicheFrais($userSaisi);
              $tabSecond = obtenirTableauMontantFrais();
              
              $montantTotal = 0;
              $requete = obtenirReqEltsHorsForfaitFicheFrais($moisSaisi, $userSaisi);
              $idJeuEltsHorsForfait = mysql_query($requete, $idConnexion);
              while($data = mysql_fetch_array($idJeuEltsHorsForfait))
                {
                    $montantTotal += $data['montant'];
                }
             
              }      
      }   
      ?>

<!-- Division principale -->
  <div id="contenu">
      <h2>Liste des Visiteurs ayant des fiches de Frais validées et non-remboursées</h2>
      <h3>Visiteur à sélectionner : </h3>
<form action="" method="post">
      <div class="corpsForm">
          <input type="hidden" name="etape" value="validerConsult" />
      <p>
        <label for="lstMois">Mois : </label>

        <select id="lstMois" name="lstMois" title="Sélectionnez le mois souhaité pour la fiche de frais">
            <?php
                // on propose tous les mois pour lesquels le visiteur a une fiche de frais
                $req = obtenirReqMoisFrais();
                $idJeuMois = mysql_query($req, $idConnexion);
                $lgMois = mysql_fetch_assoc($idJeuMois);
                while ( is_array($lgMois) ) 
                    {
                    $mois = $lgMois["mois"];
                    $noMois = intval(substr($mois, 4, 2));
                    $annee = intval(substr($mois, 0, 4));
                    ?>    
                    <option value="<?php echo $mois; ?>"<?php if ($moisSaisi == $mois) { ?> selected="selected"<?php } ?>><?php echo obtenirLibelleMois($noMois) . " " . $annee; ?></option>
                    <?php
                    $lgMois = mysql_fetch_assoc($idJeuMois);        
                    }
                mysql_free_result($idJeuMois);
            ?>
        </select>
      </p> 
      <!--ici séparation entre les deux listes déroulante Mois et Vsisiteur -->
      <input type="hidden" name="etape1" value="validerConsult1" />
      <p>
        <label for="lstVisiteur">Visiteur : </label>

        <select id="lstVisiteur" name="lstVisiteur" title="Sélectionnez le Visiteur souhaité pour la fiche de frais">
            <?php
            // on propose tous les visiteurs pour lesquels le visiteur a une fiche de frais
            $req1 = obtenirReqFraisVisiteur();               
            $idJeuVisiteur = mysql_query($req1,$idConnexion);   
            while($genre = mysql_fetch_row($idJeuVisiteur))
                 {
                 ?>
                 <option value=<?php echo "$genre[0]"?>><?php echo "$genre[0]" ?></option>
                 <?php
                 }                    
                 ?>               
        </select>
        </div>
    <div class="piedForm">
            <p>
            <input id="ok" type="submit" value="Valider" size="20"
            title="Demandez à consulter cette fiche de frais" />
            <input id="annuler" type="reset" value="Effacer" size="20" />
            </p> 
    </div>        
</form>
      
<?php      

// demande et affichage des différents éléments (forfaitisés et non forfaitisés)
// de la fiche de frais demandée, uniquement si pas d'erreur détecté au contrôle
    if ( $etape == "validerConsult" ) {
        if ( nbErreurs($tabErreurs) > 0 ) 
            {
            echo toStringErreurs($tabErreurs) ;
            }
            else {
         
?>
              <center><form action="ValidationMiseAJour.php">
              <input type=submit value="Payer cette Fiche"/>
              </form></center>
      
    <h3>Fiche de frais du mois de <?php echo obtenirLibelleMois(intval(substr($moisSaisi,4,2))) . " " . substr($moisSaisi,0,4); ?> : 
    <em><?php echo $tabFicheFrais["libelleEtat"]; ?> </em>
    depuis le <em><?php echo $tabFicheFrais["dateModif"]; ?></em></h3>
    <div class="encadre">
    <?php
    while($data = mysql_fetch_array($tabFirst))
    {
        foreach($tabSecond as $cle => $valeur)
        {
           if($cle == $data['idFraisForfait'])
           {
                $montantTotal += $data['quantite']*$valeur;
           }
        }
    }
    
    ?>
        
                <p>Montant validé : <?php echo $montantTotal ;
                $_SESSION['montantTotal'] = $montantTotal;

        ?>              
    </p>
            <?php          
            //affichage des elements en fonction des deux liste déroulante
            $req = obtenirReqNbFicheFrais($moisSaisi, $userSaisi);
            $idJeuEltsFraisForfait = mysql_query($req, $idConnexion) or die(mysql_error());
            echo mysql_error($idConnexion);
            $tabEltsFraisForfait = array();
                while ( $lgEltForfait = mysql_fetch_array($idJeuEltsFraisForfait)) 
                    {
                    $tabEltsFraisForfait[$lgEltForfait["libelle"]] = $lgEltForfait["quantite"];
                    }
                    mysql_free_result($idJeuEltsFraisForfait);
            ?>
  	<table class="listeLegere">
  	   <caption>Quantités des éléments forfaitisés</caption>
        <tr>
            <?php

            foreach ( $tabEltsFraisForfait as $unLibelle => $uneQuantite ) 
            {
            ?>
                <th><?php echo $unLibelle ; ?></th>            
            <?php
            }
            ?>
        </tr>
        <tr>
            <?php
            // second parcours du tableau des frais forfaitisés du visiteur connecté
            // pour afficher la ligne des quantités des frais forfaitisés
            foreach ( $tabEltsFraisForfait as $unLibelle => $uneQuantite ) 
            {
            ?>
                <td class="qteForfait"><?php echo $uneQuantite ; ?></td>
            <?php
            }
            ?>
        </tr>
    </table>
  	<table class="listeLegere">
  	   <caption>Descriptif des éléments hors forfait - <?php echo $tabFicheFrais["nbJustificatifs"]; ?> justificatifs reçus -
       </caption>
             <tr>
                <th class="date">Date</th>
                <th class="libelle">Libellé</th>
                <th class="montant">Montant</th>                
             </tr>
<?php          
            // demande de la requête pour obtenir la liste des éléments hors
            // forfait du visiteur connecté pour le mois demandé
            $requete = obtenirReqEltsHorsForfaitFicheFrais($moisSaisi, $userSaisi);
            $idJeuEltsHorsForfait = mysql_query($requete, $idConnexion);
            $lgEltHorsForfait = mysql_fetch_assoc($idJeuEltsHorsForfait);
            
            // parcours des éléments hors forfait 
            while ( is_array($lgEltHorsForfait) ) {
            ?>
                <tr>
                   <td><?php echo $lgEltHorsForfait["date"] ; ?></td>
                   <td><?php echo filtrerChainePourNavig($lgEltHorsForfait["libelle"]) ; ?></td>
                   <td><?php echo $lgEltHorsForfait["montant"] ; ?></td>
                </tr>
            <?php
                $lgEltHorsForfait = mysql_fetch_assoc($idJeuEltsHorsForfait);
            }
            mysql_free_result($idJeuEltsHorsForfait);
  ?>
    </table>
  </div>
<?php
       }   
    }
    
?>    
  </div>
<?php        
  require($repInclude . "_pied.inc.html");
  require($repInclude . "_fin.inc.php");
?> 