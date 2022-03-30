//
// Permet de contrôler le mécanisme de basculement de la section
//	des questions/réponses de la page.
//
$( "#faq button" ).click( function ()
{
	// On bascule d'abord l'état d'activation du bouton.
	$( this ).toggleClass( "active" );

	// On récupère alors l'élément suivant le bouton.
	//	Note : on doit récupérer ici un élément <p>.
	const content = $( this ).next();

	// On vérifie ensuite si une taille maximale a été définie.
	if ( content.css( "maxHeight" ) != "0px" )
	{
		// Si c'est le cas, on supprime nos règles personnalisées.
		content.removeAttr( "style" );
	}
	else
	{
		// Dans le cas contraire, on définit plusieurs règles pour
		//	permettre l'apparition du paragraphe.
		content.css( "maxHeight", content.prop( "scrollHeight" ) + "px" );
		content.css( "paddingTop", "0.5rem" );
		content.css( "paddingBottom", "0.5rem" );
	}
} );