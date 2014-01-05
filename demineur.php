<?php session_start();?>
<?php require('fonctions.php');?>
<?php handleReset();?>
<?php handleClick();?>
<!DOCTYPE html>
<html lang="fr">
<head>
	<meta charset="utf-8">
	<title>Demineur Yann ROLLAND</title>
	<link rel="stylesheet" type="text/css" href="style.css">
</head>
<body>
	<div id="containner">
		<h1>Démineur de Yann ROLLAND, v42</h1>
		<div id="wrapper">
			<table id="plateau">
				<?php affPlateau(); ?>
			</table>
		</div>
		<?php affInfo(); ?>
		<div id="bouton_nouv">
			<form method="get">
				lignes : <input type="number" name="nblignes" id="nblignes" min="<?php echo MINLIG;?>" max="<?php echo MAXLIG;?>" value="<?php echo $_SESSION['nblignes'];?>">
				colonnes : <input type="number" name="nbcolonnes" id="nbcolonnes" min="<?php echo MINCOL;?>" max="<?php echo MAXCOL;?>" value="<?php echo $_SESSION['nbcolonnes'];?>">
				<input type="submit" value="Nouvelle Partie">
			</form>
		</div>
	</div>
</body>
</html>