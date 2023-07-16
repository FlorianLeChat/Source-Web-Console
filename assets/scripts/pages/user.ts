// Importation des feuilles de style.
import "../../styles/desktop/user.scss";
import "../../styles/phone/user.scss";
import "../../styles/tablet/user.scss";

// Importation des fonctions et constantes globales.
import "../global";
import { addQueuedNotification } from "../functions";

//
// Permet d'envoyer les demandes de modification ou de suppression
//  des informations d'authentification vers le serveur.
//
const account = $( "#account" );

account.on( "click", "[data-action]", async ( event ) =>
{
	// On cesse d'abord le comportement par défaut.
	event.preventDefault();

	// On vérifie après si l'utilisateur veut réellement supprimer
	//  son compte utilisateur.
	const target = $( event.target );
	const action = target.data( "action" );

	if ( action === "remove" && !confirm( window.edit_remove ) )
	{
		return;
	}

	// On réalise alors la requête AJAX.
	const response = await fetch( target.data( "route" ), {
		method: action === "update" ? "PUT" : "DELETE",
		headers: {
			"Content-Type": "application/x-www-form-urlencoded"
		},
		body: new URLSearchParams( {
			// Jeton de sécurité (CSRF).
			token: account.find( `[name = token-${ action }]` ).val() as string,

			// Valeur du nouveau nom d'utilisateur.
			username: account.find( "[name = username]" ).val() as string,

			// Valeur du nouveau mot de passe.
			password: account.find( "[name = password]" ).val() as string
		} )
	} );

	// On affiche ensuite un message de confirmation ou d'erreur.
	addQueuedNotification( await response.text(), response.ok ? 3 : 1 );

	// On réinitialise enfin le formulaire après une
	//  mise à jour des informations.
	if ( action === "update" )
	{
		target.closest( "form" )[ 0 ].reset();
	}
} );

//
// Permet d'envoyer les demandes de déconnexion et de reconnexion
//  au compte utilisateur.
//
const actions = $( "#actions" );

actions.on( "click", "[type = submit]", async ( event ) =>
{
	// On cesse d'abord le comportement par défaut.
	event.preventDefault();

	// On réalise ensuite la requête AJAX.
	const target = $( event.target );
	const response = await fetch( target.data( "route" ), {
		method: "POST",
		headers: {
			"Content-Type": "application/x-www-form-urlencoded"
		},
		body: new URLSearchParams( {
			// Jeton de sécurité (CSRF).
			token: actions.find( `[name = token-${ target.data( "action" ) }]` ).val() as string,

			// Valeur du nouveau nom d'utilisateur.
			username: actions.find( "[name = username]" ).val() as string,

			// Valeur du nouveau mot de passe.
			password: actions.find( "[name = password]" ).val() as string
		} )
	} );

	// On affiche après un message de confirmation ou d'erreur.
	addQueuedNotification( await response.text(), response.ok ? 3 : 1 );

	// On vérifie si la requête a été effectuée avec succès.
	if ( response.ok )
	{
		// Dans ce cas, on actualise enfin la page après 3 secondes.
		setTimeout( () =>
		{
			window.location.reload();
		}, 3000 );
	}
} );

//
// Permet de modifier le comportement par défaut de la seconde
//  partie du formulaire d'inscription (oui c'est du recyclage).
//
const register = $( "#register" );
const submit = register.find( "input[type = submit]" );
submit.data( "action", "insert" );

//
// Permet d'envoyer les demandes d'ajout d'un nouveau serveur dans
//  la base de données.
//
submit.on( "click", async ( event ) =>
{
	// On cesse d'abord le comportement par défaut.
	event.preventDefault();

	// On réalise ensuite la requête AJAX.
	const target = $( event.target );
	const form = target.parent();
	const response = await fetch( register.data( "route" ), {
		method: "POST",
		headers: {
			"Content-Type": "application/x-www-form-urlencoded"
		},
		body: new URLSearchParams( {
			// Jeton de sécurité (CSRF).
			token: form.find( "[name = token]" ).val() as string,

			// Nom d'utilisateur et mot de passe du compte utilisateur.
			username: form.find( "[name = username]" ).val() as string,
			password: form.find( "[name = password]" ).val() as string,

			// Informations du serveur.
			server_address: form.find( "[name = address]" ).val() as string,
			server_port: form.find( "[name = port]" ).val() as string,
			server_password: form.find( "[name = password]" ).val() as string
		} )
	} );

	// On affiche après un message de confirmation ou d'erreur.
	addQueuedNotification( await response.text(), response.ok ? 3 : 1 );

	// On vérifie si la requête a été effectuée avec succès.
	if ( response.ok )
	{
		// Dans ce cas, on réinitialise enfin l'entièreté du formulaire.
		const element = target.closest( "form" )[ 0 ] as HTMLFormElement;
		element.reset();
	}
} );