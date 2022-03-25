//
// Permet de gérer les mécanismes du formulaire de contact.
//
const contact = $( "#contact" );

contact.find( "input[type = submit]" ).click( function ( event )
{
	if ( true )
	{
		// On réalise les vérifications avant de soumettre le formulaire.
		event.preventDefault();
	}
} );

contact.find( "input[type = reset]" ).click( function ()
{
	// On cache le formulaire à la demande de l'utilisateur.
	contact.hide();
} );

//
// Permet d'ouvrir le formulaire de contact via le pied de page.
//
const footer = $( "footer" );

footer.find( "a[href = \"javascript:void(0);\"]" ).click( function ()
{
	contact.show();
} );

//
// Permet de désactiver le mécanisme de glissement des liens.
//
const links = $( "a" );

links.mousedown( function ( event )
{
	event.preventDefault();
} );

//
// Permet de bloquer le renvoie des formulaires lors du rafraîchissement
//	de la page par l'utilisateur.
// 	Source : https://stackoverflow.com/a/45656609
//
if ( window.history.replaceState && window.location.hostname != "localhost" )
{
	window.history.replaceState( null, null, window.location.href );
}