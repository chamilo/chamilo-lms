<page footer="form">
	<h1>Test de formulaire</h1><br>
	<br>
	<form>
		<input type="hidden" name="test" >
		Vous utilisez cette librairie dans le cadre :
		<ul style="list-style: none">
			<li><input type="checkbox" name="cadre1" checked="checked" > du boulot</li>
			<li><input type="checkbox" name="cadre2" > perso</li>
		</ul>
		Vous êtes :
		<ul style="list-style: none">
			<li><input type="radio" name="sexe" > un homme</li>
			<li><input type="radio" name="sexe" > une femme</li>
		</ul>
		Vous avez : 
		<select name="age" >
			<option value="15">moins de 15 ans</option>
			<option value="20">entre 15 et 20 ans</option>
			<option value="25">entre 20 et 25 ans</option>
			<option value="30">entre 25 et 30 ans</option>
			<option value="40">plus de 30 ans</option>
		</select><br>
		<br>
		Vous aimez : 
		<select name="aime" size="5" multiple="multiple">
			<option value="ch1">l'informatique</option>
			<option value="ch2">le cinéma</option>
			<option value="ch3">le sport</option>
			<option value="ch4">la littérature</option>
			<option value="ch5">autre</option>
		</select><br>
		<br>
		Votre phrase fétiche : <input type="text" name="phrase" value="cette lib est  géniale !!!" style="width: 100mm"><br>
		<br>
		Un commentaire ?<br>
		<textarea name="comment" rows="3" cols="30">rien de particulier</textarea><br>
		<br>
		<input type="button" value="Imprimer" onclick="print(true);">
	</form>
</page>