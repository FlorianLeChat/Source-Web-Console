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