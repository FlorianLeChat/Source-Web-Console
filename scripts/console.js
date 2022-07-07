//
// Permet d'envoyer les entrées utilisateurs personnalisées
//	au serveur distant.
//
$( "#controller button" ).click( ( event ) =>
{
	// On récupère le contenu de l'entrée utilisateur avant
	//	de le vérifie pour la prochaine étape.
	const input = $( event.target ).prev().val();

	if ( input.trim() === "" || input.length === 0 )
	{
		// C'est une chaîne vide.
		return "";
	}

	// On envoie ensuite le contenu au serveur distant.
	sendRemoteAction( "console", input );

	// Une fois réalisée, on ajoute une entrée dans l'historique
	//	des entrées juste au-dessous.
	$( event.target ).parent().parent().find( "ul" ).append( `<li>${ input }</li>` );

	// On réinitialise enfin le champ de saisie.
	$( event.target ).prev().val( "" );
} );