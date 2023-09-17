// Importation de la feuille de style.
import "../../styles/desktop/actions.scss";

// Importation des fonctions et constantes communes.
import "../global";
import { sendRemoteAction, addQueuedNotification } from "../functions";

//
// Permet d'envoyer des requêtes d'action lors du clic sur l'un des
//  boutons présents (par défaut ou non) sur la page.
//
$( "#actions ul:first-of-type li, .switch span" ).on( "click", async ( event ) =>
{
	// Requête classique en fonction du bouton.
	const target = $( event.target );
	const element = target.is( "span" ) ? target.parent().parent() : target;

	if ( element.data( "action" ) )
	{
		// Blocage du bouton pour éviter les abus.
		element.addClass( "disabled" );

		// Exécution de la requête d'action.
		const state = await sendRemoteAction(
			element.data( "token" ),
			element.data( "route" ),
			element.data( "action" )
		);

		if ( !state )
		{
			// Libération du bouton en cas d'erreur.
			element.removeClass( "disabled" );
		}
	}
} );

$( "#actions ul:first-of-type li:first-of-type" ).on(
	"dblclick",
	async ( event ) =>
	{
		// Requête d'arrêt forcée
		const target = $( event.target );
		const parent = target.parent();

		// Blocage du bouton pour éviter les abus.
		parent.addClass( "disabled" );

		// Exécution de la requête d'action.
		const state = await sendRemoteAction(
			target.data( "token" ),
			target.data( "route" ),
			target.data( "action" )
		);

		if ( !state )
		{
			// Libération du bouton en cas d'erreur.
			parent.removeClass( "disabled" );
		}
	}
);

//
// Permet de gérer les demandes d'ajout, exécution ou de suppression
//  des commandes personnalisées par défaut ou créées par l'utilisateur.
//
const commands = $( "#commands" );

commands.on( "click", "[data-action = add]", async ( event ) =>
{
	// On récupère d'abord les informations de la nouvelle
	//  commande personnalisée.
	const title = prompt( window.command_add_title );
	const content = prompt( window.command_add_content );

	if ( !title || !content )
	{
		return;
	}

	// On bloque également les boutons de soumission pour éviter les abus.
	const target = $( event.target );
	const element =
		target.is( "em" ) || target.is( "span" ) ? target.parent() : target;
	element.prop( "disabled", true );

	// On réalise ensuite la requête AJAX.
	const response = await fetch( element.data( "route" ), {
		method: "POST",
		headers: {
			"Content-Type": "application/x-www-form-urlencoded"
		},
		body: new URLSearchParams( {
			// Jeton de sécurité (CSRF).
			token: element.data( "token" ),

			// Titre de la commande personnalisée.
			title,

			// Contenu de la commande personnalisée.
			content
		} )
	} );

	// On affiche après un message de confirmation ou d'erreur.
	addQueuedNotification( await response.text(), response.ok ? 3 : 1 );

	// On vérifie si la requête a été effectuée avec succès.
	if ( response.ok )
	{
		// Dans ce cas, on actualise alors la page après 3 secondes.
		setTimeout( () =>
		{
			window.location.reload();
		}, 3000 );
	}
	else
	{
		// On libère enfin les boutons de soumission et
		//  de réinitialisation en cas d'erreur.
		element.prop( "disabled", false );
	}
} );

commands.on( "click", "[data-action = remove]", async ( event ) =>
{
	// On vérifie d'abord si l'utilisateur demande a supprimer la commande.
	if ( confirm( window.edit_remove ) )
	{
		// On bloque également les boutons de soumission pour éviter les abus.
		commands.find( "[type = button]" ).prop( "disabled", true );

		// On réalise ensuite la requête AJAX.
		const target = $( event.target );
		const response = await fetch( target.data( "route" ), {
			method: "POST",
			headers: {
				"Content-Type": "application/x-www-form-urlencoded"
			},
			body: new URLSearchParams( {
				// Jeton de sécurité (CSRF).
				token: target.data( "token" ),

				// Identifiant unique de la commande personnalisée.
				id: target.parent().data( "command" )
			} )
		} );

		// On affiche après un message de confirmation ou d'erreur.
		addQueuedNotification( await response.text(), response.ok ? 3 : 1 );

		// On vérifie si la requête a été effectuée avec succès.
		if ( response.ok )
		{
			// Dans ce cas, on actualise alors la page après 3 secondes.
			setTimeout( () =>
			{
				window.location.reload();
			}, 3000 );
		}
		else
		{
			// On libère enfin les boutons de soumission en cas d'erreur.
			commands.find( "[type = button]" ).prop( "disabled", false );
		}
	}
} );

commands.on( "click", "[data-action = execute]", async ( event ) =>
{
	// Exécution de la commande personnalisée.
	const target = $( event.target );
	const parent = target.parent();
	const element = parent.data( "route" ) ? parent : target;

	// Blocage du bouton pour éviter les abus.
	element.prop( "disabled", true );

	// Exécution de la requête d'action.
	const state = await sendRemoteAction(
		element.data( "token" ),
		element.data( "route" ),
		element.parent().data( "command" ) ?? element.data( "command" ),
		prompt( window.execute_value ) ?? ""
	);

	if ( !state )
	{
		// Libération du bouton en cas d'erreur.
		element.prop( "disabled", false );
	}
} );