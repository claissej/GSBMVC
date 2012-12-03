
<?php
include("vues/v_sommaireC.php");
  
  
  // acquisition des données entrées, ici le numéro de mois et l'étape du traitement
  //verifie si les donnéesrentrer existe ou pas sinon retourne une valeur par default
  $moisSaisi=lireDonneePost("lstMois", "");
  $etape=lireDonneePost("etape",""); 
  $userSaisi=lireDonneePost("lstVisiteur","");
  $tabErreurs = array();
  
  if ($etape != "demanderConsult" && $etape != "validerConsult") 
      {
      // si autre valeur, on considère que c'est le début du traitement
      $etape = "demanderConsult";        
      } 
  if ($etape == "validerConsult") 
      { // l'utilisateur valide ses nouvelles données
      // vérification de l'existence de la fiche de frais pour le mois demandé
      $existeFicheFrais = $pdo->existeFicheFrais($moisSaisi, $userSaisi);
      // si elle n'existe pas, on la crée avec les élements frais forfaitisés à 0
          if ( !$existeFicheFrais ) 
          {
              ajouterErreur("Le mois demandé est invalide");
          }
          else 
              {
              // récupération des données sur la fiche de frais demandée
              $tabFicheFrais = $pdo->obtenirDetailFicheFrais($moisSaisi, $userSaisi);
              $montantTotal = $pdo->obtenirMontantTotal($userSaisi, $moisSaisi);
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
                $idJeuMois = $pdo->obtenirMoisFrais();
                foreach ($idJeuMois as $mois) {
                    $noMois = intval(substr($mois, 4, 2));
                    $annee = intval(substr($mois, 0, 4));
                    ?>    
                    <option value="<?php echo $mois; ?>"<?php if ($moisSaisi == $mois) { ?> selected="selected"<?php } ?>><?php echo obtenirLibelleMois($noMois) . " " . $annee ?></option>
                    <?php
                }
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
            $idJeuVisiteur = $pdo->obtenirFraisVisiteur();
                foreach ($idJeuVisiteur as $visiteurs) {
                 ?>
                 <option value=<?php echo $visiteurs; ?>><?php echo $visiteurs; ?></option>
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
    if(isset($_POST['valider']))
    {
        $pdo->valiserFiche($_SESSION['user'], $_SESSION['mois']);
        echo "La fiche de frais de \"".$_SESSION['user']."\" a bien été validée.<br>";
    }
// demande et affichage des différents éléments (forfaitisés et non forfaitisés)
// de la fiche de frais demandée, uniquement si pas d'erreur détecté au contrôle
    if ( $etape == "validerConsult" ) {
        if ( nbErreurs() > 0 )
            {
            echo toStringErreurs() ;
            }
            else {
            $_SESSION['mois']=$moisSaisi;
            $_SESSION['user']=$userSaisi;
?>
              <center><form method="POST" action="#">
              <input type=submit name="valider" value="Payer cette Fiche"/>
              </form></center>
      
    <h3>Fiche de frais du mois de <?php echo obtenirLibelleMois(intval(substr($moisSaisi,4,2))) . " " . substr($moisSaisi,0,4); ?> : 
    <em><?php echo $tabFicheFrais["libelleEtat"]; ?> </em>
    depuis le <em><?php echo $tabFicheFrais["dateModif"]; ?></em></h3>
    <div class="encadre">
                <p>Montant validé : <?php $resMontant = $pdo->obtenirDetailFicheFrais($moisSaisi, $userSaisi);
                $montantTotal = $resMontant['montantValide'];
                $_SESSION['montantTotal'] = $montantTotal;
                echo $montantTotal;

        ?>              
    </p>
            <?php          
            //affichage des elements en fonction des deux liste déroulante
            $tabEltsFraisForfait = $pdo->obtenirTabEltsFraisForfait($userSaisi, $moisSaisi);
                   
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
            
            $lgEltHorsForfait = $pdo->obtenirLgEltHorsForfait($userSaisi, $moisSaisi);
            // parcours des éléments hors forfait 
            foreach ($lgEltHorsForfait as $HorsForfait)
            {
            ?>
                <tr>
                   <td><?php echo $HorsForfait["date"] ; ?></td>
                   <td><?php echo filtrerChainePourNavig($HorsForfait["libelle"]) ; ?></td>
                   <td><?php echo $HorsForfait["montant"] ; ?></td>
                </tr>
            <?php
            }
            
  ?>
    </table>
  </div>
<?php
       }   
    }
    
?>    
  </div>
