//
// Permet d'envoyer des requêtes d'action lors du clic sur l'un des
//	boutons présents (par défaut ou non) sur la page.
//
$( "#actions ul:first-of-type li, .switch span" ).click( function ()
{
	// Requête classique en fonction du bouton.
	sendRemoteAction( $( this ).attr( "data-action" ) );
} );

//
// Permet de gérer les demandes d'ajout, exécution ou de suppression
//	des commandes personnalisées par défaut ou créées par l'utilisateur.
//	Note : dans certains cas, on doit rafraîchir la page.
//
$( "#commands li[data-action = add]" ).click( function ()
{
	// Ajout d'une commande personnalisée.
	sendRemoteAction( prompt( command_add_name ) + "|" + prompt( command_add_content ), "#ADD#" );
	location.reload();
} );

$( "#commands button[data-action = remove]" ).click( function ()
{
	// Suppression de la commande personnalisée.
	sendRemoteAction( $( this ).parent().attr( "data-command" ), "#REMOVE#" );
	location.reload();
} );

$( "#commands button[data-action = execute]" ).click( function ()
{
	// Exécution de la requête personnalisée.
	sendRemoteAction( $( this ).parent().attr( "data-command" ), prompt( execute_value ) );
} );