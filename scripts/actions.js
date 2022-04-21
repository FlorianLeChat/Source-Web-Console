//
// Permet d'envoyer des requêtes d'action lors du clic sur l'un des
//	boutons présents (par défaut ou non) sur la page.
//
$( "#actions ul:first-of-type li, .switch span" ).click( function ()
{
	// Requête classique en fonction du bouton.
	sendRemoteAction( $( this ).attr( "data-action" ) );
} );