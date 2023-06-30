// Importation des feuilles de style.
import "../../styles/desktop/actions.scss";
import "../../styles/phone/actions.scss";
import "../../styles/tablet/actions.scss";

// Importation des fonctions et constantes communes.
import "../global";
import { sendRemoteAction } from "../functions";

//
// Permet d'envoyer des requêtes d'action lors du clic sur l'un des
//  boutons présents (par défaut ou non) sur la page.
//
$( "#actions ul:first-of-type li, .switch span" ).on( "click", ( event ) =>
{
	// Requête classique en fonction du bouton.
	const target = $( event.target ).is( "span" ) ? $( event.target ).parent().parent() : $( event.target );
	const action = target.attr( "data-action" );

	if ( action )
	{
		sendRemoteAction( target.attr( "data-token" ), target.attr( "data-route" ), target.attr( "data-action" ) );
	}
} );

$( "#actions ul:first-of-type li:first-of-type" ).on( "dblclick", ( event ) =>
{
	// Requête d'arrêt forcée
	const target = $( event.target );
	sendRemoteAction( target.attr( "data-token" ), target.attr( "data-route" ), target.attr( "data-action" ) );
} );

//
// Permet de gérer les demandes d'ajout, exécution ou de suppression
//  des commandes personnalisées par défaut ou créées par l'utilisateur.
//  Note : dans certains cas, on doit rafraîchir la page.
//
$( "#commands li[data-action = add]" ).on( "click", () =>
{
	// Ajout d'une commande personnalisée.
	sendRemoteAction( `${ prompt( window.command_add_name ) }|${ prompt( window.command_add_content ) }`, "#ADD#" );
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
	sendRemoteAction( $( event.target ).parent().attr( "data-command" ), prompt( window.execute_value ) );
} );