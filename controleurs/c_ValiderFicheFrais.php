<?php
 include("vues/v_sommaireC.php");

 $listeVisiteur=$pdo->visiteurFicheEnCours();
 $listeMois=$pdo->moisFicheEnCours();
 
 if(isset($_GET['select']))
 {
  if(isset($_POST['visiteur']) && isset($_POST['mois']))
  {
   $_SESSION['visiteur'] = $_POST['visiteur'];
   $_SESSION['mois'] = $_POST['mois'];
  }
  if(isset($_GET['valide']))
  {
   $pdo->majEtatFicheFrais($_SESSION['visiteur'],$_SESSION['mois'],"VA");
   echo"<script> alert('La fiche a été validée.');";
   echo"window.location = 'index.php?uc=validerFrais&select'</script>";
  }
  if(isset($_GET['suppr']))
  {
   $pdo->supprimerFraisHorsForfait($_POST['id']);
  }
  $horsForfait=$pdo->getLesFraisHorsForfait($_SESSION['visiteur'], $_SESSION['mois']);
 }
 ?>
 <div id="contenu">
  <h2>Valider les fiches de frais des visiteurs médicaux</h2>
  <h3>Visiteur à sélectionner :</h3>
  <div class="corpsForm">
   <form method="POST" action="index.php?uc=validerFrais&select"><br/>
    <label for="visiteur">Visiteur :</label>
    <select name="visiteur">
     <?php
      while($visiteur=$listeVisiteur->fetch())
      {
       if(isset($_SESSION['visiteur']) && $_SESSION['visiteur']==$visiteur['id'])
       {
        echo"<option selected value='".$visiteur['id']."'>".$visiteur['id']." ".$visiteur['nom']." ".$visiteur['prenom']."</option>\n";
       }
       else
       {
        echo"<option value='".$visiteur['id']."'>".$visiteur['id']." ".$visiteur['nom']." ".$visiteur['prenom']."</option>\n";
       }
      }
     ?>
    </select><br/><br/>
    <label for="mois">Mois :</label>
    <select name='mois'>
     <?php    
      while($mois=$listeMois->fetch())
      {
       if(isset($_SESSION['mois']) && $_SESSION['mois']==$mois['mois'])
       {
        echo"<option selected value='".$mois['mois']."'>".substr($mois['mois'],4,5)."/".substr($mois['mois'],0,4)."</option>\n";
       }
       else
       {
        echo"<option value='".$mois['mois']."'>".substr($mois['mois'],4,5)."/".substr($mois['mois'],0,4)."</option>\n";
       }
      }
     ?>  
    </select><br/>
    <input type="submit" value="Valider" style="left: 90%; position: relative">
   </form>
  </div>
  <?php
   if(isset($_GET['select']))
   {
    $fiches = $pdo->getLesFraisForfait($_SESSION['visiteur'],$_SESSION['mois']);
    if(empty($fiches))
    {
     echo "<br>";
     echo "Ce visiteur ne possede pas de fiche de frais pour ce mois.";
    }
    else
    {
     ?>
      <form method="POST" action="index.php?uc=validerFrais&select&valide">
       <input type='Submit' value='Valider cette fiche' style='left: 37%; position: relative'>
      </form>
      <div class="corpsForm">
       <form method="POST" action="index.php?uc=validerFrais&select&modif">
        <h4>Eléments forfaitisés :</h4>
         <?php 
          foreach($fiches as $tab)
          {
           echo"<label for=\"".$tab['libelle']."\"> ".$tab['libelle']." :</label>";
           echo "<input type=\"text\" Value=\"".$tab['quantite']."\" name=\"".$tab['idfrais']."\"><br>";
          }
          if(isset($_GET['modif']))
          {
           echo "Element modifiés avec succès !";
          }
         ?>
        <br>
       <input type="Submit" value="Valider" style="left: 87%; position: relative">
       </form>
      </div>
      <h4>Descriptif des éléments hors-forfait</h4>
      <table width="100%" Cellpadding="10">
       <tr>
        <td>Date</td>
        <td>Libellé</td>
        <td>Montant</td>
        <td></td>
       </tr>
       <?php 
        foreach($horsForfait as $tabHorsForfait)
        {
         ?>
          <tr>
           <td>
            <?php echo $tabHorsForfait['date']?>
           </td>
           <td>
            <?php echo $tabHorsForfait['libelle']?>
           </td>
           <td>
            <?php echo $tabHorsForfait['montant']?>
           </td>
           <td>
            <form method="POST" action ="index.php?uc=validerFrais&select&suppr" onsubmit="return confirm('Ce frais va être supprimé.\nContinuer ?');">
             <input type="submit" value="Supprimer" />
             <input type='hidden' name='id' value='<?php echo $tabHorsForfait['id']; ?>'>
            </form>
           </td>
          </tr>
         <?php 
        }
       ?>
      </table>
     <?php
    }
   }
  ?>
 </div>