// Importation des feuilles de style.
import "../styles/desktop/actions.scss";
import "../styles/phone/actions.scss";
import "../styles/tablet/actions.scss";

// Importation des fonctions globales.
import { sendRemoteAction } from "./_functions";

//
// Permet d'envoyer des requêtes d'action lors du clic sur l'un des
//  boutons présents (par défaut ou non) sur la page.
//
$( "#actions ul:first-of-type li, .switch span" ).on( "click", ( event ) =>
{
	// Requête classique en fonction du bouton.
	sendRemoteAction( $( event.target ).attr( "data-action" ) );
} );

//
// Permet de gérer les demandes d'ajout, exécution ou de suppression
//  des commandes personnalisées par défaut ou créées par l'utilisateur.
//  Note : dans certains cas, on doit rafraîchir la page.
//
$( "#commands li[data-action = add]" ).on( "click", () =>
{
	// Ajout d'une commande personnalisée.
	sendRemoteAction( `${ prompt( command_add_name ) }|${ prompt( command_add_content ) }`, "#ADD#" );
	window.location.reload();
} );

$( "#commands button[data-action = remove]" ).on( "click", ( event ) =>
{
	// Suppression de la commande personnalisée.
	sendRemoteAction( $( event.target ).parent().attr( "data-command" ), "#REMOVE#" );
	window.location.reload();
} );

$( "#commands button[data-action = execute]" ).on( "click", ( event ) =>
{
	// Exécution de la requête personnalisée.
	sendRemoteAction( $( event.target ).parent().attr( "data-command" ), prompt( execute_value ) );
} );