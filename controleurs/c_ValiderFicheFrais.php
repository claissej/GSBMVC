<?php
include("vues/v_sommaireC.php");

$listeVisiteur=$pdo->visiteurFicheEnCours();
$listeMois=$pdo->moisFicheEnCours();

if($_GET['action']=="selectionnerVisiteur" && isset($_GET['action2']) && $_GET['action2']=="FicheValide")
{
    $pdo->majEtatFicheFrais($_POST['Id'],$_POST['mois'],"VA");
    echo"<script> alert('Les modifications ont bien été éffectuées.');";
    echo"window.location = 'index.php?uc=validerFrais&action=selectionnerVisiteur'</script>";
}

if($_GET['action']=="VisiteurSelected")
{
     if(isset($_GET['action2']) && $_GET['action2']=="FraitHorsForfait")
        {
            $pdo->supprimerFraisHorsForfait($_POST['IdHorsForfait']);
        }
    $FraitHorsForfait=$pdo->getLesFraisHorsForfait($_POST['Id'],$_POST['mois']);
}
?>

 <div id="contenu">
      <h2>Valider les fiches de frais des visiteurs médicaux</h2>
      <h3><legend>Visiteur à sélectionner :</legend></h3>
	    <div class="corpsForm">
                <form method="POST" action="index.php?uc=validerFrais&action=VisiteurSelected">
                <br/>
            <label for="visiteur">Visiteur :</label>
            <select name="Id">
		
		<?php
		while($visiteur=$listeVisiteur->fetch())
		{
                    if(isset($_POST['Id']) && $_POST['Id']==$visiteur['id'])
                    {
                        echo"<option label='Visiteur' Selected value='".$visiteur['id']."'>".$visiteur['id']." ".$visiteur['nom']."</option>";
                        
                    }
                    else
                    {
                        echo"<option label='Visiteur' value='".$visiteur['id']."'>".$visiteur['id']." ".$visiteur['nom']."</option>";
                    }
		}
		
                ?>
            </select>
            <br/><br/>
            <label for="mois">Mois :</label>
		<select name='mois' id="mois">
		<?php	
		while($mois=$listeMois->fetch())
		{
                    if(isset($_POST['mois']) && $_POST['mois']==$mois['mois'])
                    {
                        echo"<option label='MoisVisiteurs' Selected value='".$mois['mois']."'>".substr($mois['mois'],4,5)."/".substr($mois['mois'],0,4)."</option>";
                        
                    }
                    else
                    {
                    echo"<option label='MoisVisiteurs' value='".$mois['mois']."'>".substr($mois['mois'],4,5)."/".substr($mois['mois'],0,4)."</option>";
                    }
		}
		?>  
                </select>
            
            </div>
        <br/>
        <input type="Submit" value="Valider" style="left: 87%; position: relative"> </form>
        <?php
        if($_GET['action']=="VisiteurSelected")
        {
           $res=$pdo->getLesFraisForfait($_POST['Id'],$_POST['mois']);
           
           if(isset($_GET['action2']) && $_GET['action2']=="FraitForfait")
             {
               $lesFrais =array();
               foreach($res as $tab)
                {
                  $lesFrais[$tab['idfrais']]=$_POST[$tab['idfrais']];
                }
                $pdo->majFraisForfait($_POST['Id'], $_POST['mois'], $lesFrais);
             }
                           
            $res=$pdo->getLesFraisForfait($_POST['Id'],$_POST['mois']);
		if(empty($res))
		{
			echo"<br> Pas de fiche de frais pour ce visiteur et ce mois ou fiche de frais déjà validée.";
			
		}
		else
		{?>
                        <hr align="left" width="100%" color="Black" size="3">
                        <form method="POST" action="index.php?uc=validerFrais&action=selectionnerVisiteur&action2=FicheValide">
                        <input TYPE='hidden' NAME='Id' VALUE='<?php echo $_POST['Id'] ?>'>
                        <input TYPE='hidden' NAME='mois' VALUE='<?php echo $_POST['mois'] ?>'>
                        <input type='Submit' value='Valider cette fiche' style='left: 37%; position: relative'>
                        </form>
                        <div class="corpsForm">
                        <form method="POST" action="index.php?uc=validerFrais&action=VisiteurSelected&action2=FraitForfait">
                        <input TYPE='hidden' NAME='Id' VALUE='<?php echo $_POST['Id'] ?>'>
                        <input TYPE='hidden' NAME='mois' VALUE='<?php echo $_POST['mois'] ?>'>
                        <fieldset>
                        <legend><h4>Eléments forfaitisés :</h4></legend>
                        <p id="FraisForfait">
                        <?php 
                        foreach($res as $tab)
                         {
                             echo'<label for="'.$tab['libelle'].'">* '.$tab['libelle'].' :</label><input size="10" maxlength="5" type="text" id="Forfait" Value="'.$tab['quantite'].'" name="'.$tab['idfrais'].'"><br><br>';
                         }
                         ?>
                         </p> 
                         <?php
                          if(isset($_GET['action2']) && $_GET['action2']=="FraitForfait")
                            {echo "Element modifiés avec succès !";}
                            ?>
                         </fieldset>
                         </div>
                        <br><input type="Submit" value="Valider" style="left: 87%; position: relative"></form>
                        <br>
			<h4>Descriptif des éléments hors-forfait</h4>
                        <table width="100%" Cellpadding="10">
			<th>Date</th><th>Libellé</th><th>Montant</th><th></th>
                  <?php 
                  
                        foreach($FraitHorsForfait as $TabHorsForfait)
                        {?>
                        <form method="POST" action ="index.php?uc=validerFrais&action=VisiteurSelected&action2=FraitHorsForfait" onsubmit="return confirm('Voulez-vous vraiment supprimer ce frais hors forfait ?');">
                        <input TYPE='hidden' NAME='Id' VALUE='<?php echo $_POST['Id'] ?>'>
                        <input TYPE='hidden' NAME='mois' VALUE='<?php echo $_POST['mois'] ?>'>
			<tr><td><?php echo $TabHorsForfait['date']?></td><td><?php echo $TabHorsForfait['libelle']?></td><td><?php echo $TabHorsForfait['montant']?></td><td align=center><input  type="Submit" value="Supprimer"/></td></tr>
                        <input TYPE='hidden' NAME='IdHorsForfait' VALUE='<?php echo $TabHorsForfait['id']; ?>'>
                        <input TYPE='hidden' NAME='LibHorsForfait' VALUE='<?php echo $TabHorsForfait['libelle']; ?>'></form>
                    <?php }
                     echo"</table>";
                    if(isset($_GET['action2']) && $_GET['action2']=="FraitHorsForfait")
                    {
                      echo "Le frais hors forfait ".$_POST['LibHorsForfait']." à été supprimé.";
                    }
                  }
       
        } ?>
 </div>