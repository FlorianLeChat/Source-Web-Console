// Importation de la feuille de style.
import "../../styles/desktop/user.scss";

// Importation des fonctions et constantes globales.
import "../global";
import { addQueuedNotification } from "../functions";

//
// Permet d'ajouter un message de succès après une connexion
//  via un fournisseur d'authentification externe (OAuth).
//
if ( window.location.search === "?oauth=1" )
{
	addQueuedNotification( window.oauth_success, 3 );
}

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

	// On bloque également le bouton de soumission pour éviter les abus.
	account.find( "[type = submit]" ).prop( "disabled", true );

	// On réalise alors la requête AJAX.
	const response = await fetch( target.data( "route" ), {
		method: action === "update" ? "PUT" : "DELETE",
		headers: {
			"Content-Type": "application/x-www-form-urlencoded"
		},
		body: new URLSearchParams( {
			// Jeton de sécurité (CSRF).
			token: target.data( "token" ),

			// Valeur du nouveau nom d'utilisateur.
			username: account.find( "[name = username]" ).val() as never,

			// Valeur du nouveau mot de passe.
			password: account.find( "[name = password]" ).val() as never
		} )
	} );

	// On affiche ensuite un message de confirmation ou d'erreur.
	addQueuedNotification( await response.text(), response.ok ? 3 : 1 );

	// On vérifie si la requête a été effectuée avec succès.
	if ( response.ok )
	{
		// On réinitialise alors le formulaire après une
		//  mise à jour des informations.
		if ( action === "update" )
		{
			target.closest( "form" )[ 0 ].reset();
		}
	}
	else
	{
		// On libère enfin le bouton de soumission en cas d'erreur.
		account.find( "[type = submit]" ).prop( "disabled", false );
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

	// On bloque également le bouton de soumission pour éviter les abus.
	actions.find( "[type = submit]" ).prop( "disabled", true );

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

			// Valeur du nouveau nom d'utilisateur.
			username: actions.find( "[name = username]" ).val() as never,

			// Valeur du nouveau mot de passe.
			password: actions.find( "[name = password]" ).val() as never
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
		// On libère enfin le bouton de soumission en cas d'erreur.
		actions.find( "[type = submit]" ).prop( "disabled", false );
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

	// On bloque également le bouton de soumission pour éviter les abus.
	register.find( "[type = submit]" ).prop( "disabled", true );

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
			token: register.data( "token" ),

			// Nom d'utilisateur et mot de passe du compte utilisateur.
			username: form.find( "[name = username]" ).val() as never,
			password: form.find( "[name = password]" ).val() as never,

			// Informations du serveur.
			server_address: form.find( "[name = address]" ).val() as never,
			server_port: form.find( "[name = port]" ).val() as never,
			server_password: form.find( "[name = password]" ).val() as never
		} )
	} );

	// On affiche après un message de confirmation ou d'erreur.
	addQueuedNotification( await response.text(), response.ok ? 3 : 1 );

	// On vérifie si la requête a été effectuée avec succès.
	if ( response.ok )
	{
		// Dans ce cas, on réinitialise alors l'entièreté du formulaire.
		const element = target.closest( "form" )[ 0 ] as HTMLFormElement;
		element.reset();
	}
	else
	{
		// On libère enfin le bouton de soumission en cas d'erreur.
		register.find( "[type = submit]" ).prop( "disabled", false );
	}
} );