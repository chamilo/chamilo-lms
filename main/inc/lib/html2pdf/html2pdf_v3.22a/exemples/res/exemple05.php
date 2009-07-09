<style type="text/css">
<!--
table
{
	width:	100%;
	border:	solid 1px #5544DD;
}

th
{
	text-align:	center;
	border:		solid 1px #113300;
	background:	#EEFFEE;
}

td
{
	text-align:	left;
	border:		solid 1px #55DD44;
}

-->
</style>
<span style="font-size: 20px; font-weight: bold">Démonstration des retour à la ligne automatique, ainsi que des sauts de page automatique<br></span>
<br>
<br>
<table>
	<thead>
		<tr>
			<th rowspan="2" style="width: 5%;">n°</th>
			<th colspan="3" style="width: 95%; font-size: 16px;">
				Titre du tableau
			</th>
		</tr>
		<tr>
			<th style="width: 25%;">Colonne 1</th>
			<th style="width: 30%;">Colonne 2</th>
			<th style="width: 40%;">Colonne 3</th>
		</tr>
	</thead>
<?php for($k=0; $k<50; $k++) { ?>
	<tr>
		<td style="width: 5%;"><?php echo $k; ?></td>
		<td style="width: 25%;">test de texte assez long pour engendrer des retours à la ligne automatique...</td>
		<td style="width: 30%;">test de texte assez long pour engendrer des retours à la ligne automatique...</td>
		<td style="width: 40%;">test de texte assez long pour engendrer des retours à la ligne automatique...</td>
	</tr>
<?php } ?>
	<tfoot>
		<tr>
			<th colspan="4" style="width: 100%; font-size: 16px;">
				bas du tableau
			</th>
		</tr>
	</tfoot>
</table>
Cool non ?<br>