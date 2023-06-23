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
$( "#account input[data-action]" ).on( "click", ( event ) =>
{
	// On cesse d'abord le comportement par défaut.
	event.preventDefault();

	// On vérifie après si l'utilisateur veut réellement supprimer
	//  son compte utilisateur.
	const action = $( event.target ).attr( "data-action" );

	if ( action === "remove" && !confirm( edit_remove ) )
	{
		return;
	}

	// On réalise ensuite la requête AJAX.
	const form = $( "#account" );

	$.post( `api/user/${ action }`, {

		// Jeton de sécurité (CSRF).
		token: form.find( `input[name = token-${ action }]` ).val(),

		// Valeur du nouveau nom d'utilisateur.
		username: form.find( "input[name = username]" ).val(),

		// Valeur du nouveau mot de passe.
		password: form.find( "input[name = password]" ).val()

	} )
		.done( ( data ) =>
		{
			// On affiche la notification de confirmation.
			addQueuedNotification( data, 3 );
		} )
		.fail( ( self ) =>
		{
			// Dans le cas contraire, on affiche un message d'erreur.
			addQueuedNotification( self.responseText, 1 );
		} );

	// On réinitialise enfin le formulaire après une
	//  mise à jour des informations.
	if ( action === "update" )
	{
		$( event.target ).parent().parent()[ 0 ].reset();
	}
} );

//
// Permet d'envoyer les demandes de déconnexion et de reconnexion
//  au compte utilisateur.
//
$( "#actions input[type = submit]" ).on( "click", ( event ) =>
{
	// On cesse d'abord le comportement par défaut.
	event.preventDefault();

	// On réalise ensuite la requête AJAX.
	const form = $( "#actions" );
	const action = $( event.target ).attr( "data-action" );

	$.post( `api/user/${ action }`, {

		// Jeton de sécurité (CSRF).
		token: form.find( `input[name = token-${ action }]` ).val(),

		// Valeur du nouveau nom d'utilisateur.
		username: form.find( "input[name = username]" ).val(),

		// Valeur du nouveau mot de passe.
		password: form.find( "input[name = password]" ).val()

	} )
		.done( ( data ) =>
		{
			// Une fois terminée, on affiche un message de confirmation.
			addQueuedNotification( data, 3 );

			// On redirige l'utilisateur quelques instants après.
			setTimeout( () =>
			{
				window.location.href = "";
			}, 5000 );
		} )
		.fail( ( self ) =>
		{
			// Dans le cas contraire, on affiche un message d'erreur.
			addQueuedNotification( self.responseText, 1 );
		} );
} );

//
// Permet de modifier le comportement par défaut de la seconde
//  partie du formulaire d'inscription (oui c'est du recyclage).
//
$( "#register input[type = submit]" ).attr( "data-action", "insert" );

//
// Permet d'envoyer les demandes d'ajout d'un nouveau serveur dans
//  la base de données.
//
$( "#register input[type = submit]" ).on( "click", ( event ) =>
{
	// On cesse d'abord le comportement par défaut.
	event.preventDefault();

	// On réalise ensuite la requête AJAX.
	const form = $( event.target ).parent();

	$.post( "includes/controllers/server_user.php", {

		// Type de l'action qui doit être effectué.
		user_action: $( event.target ).attr( "data-action" ),

		// Informations du client (obligatoires côté serveur).
		server_address: form.find( "input[name = server_address]" ).val(),
		server_port: form.find( "input[name = server_port]" ).val(),

		// Informations administrateur (facultatives).
		admin_address: form.find( "input[name = admin_address]" ).val(),
		admin_port: form.find( "input[name = admin_port]" ).val(),
		admin_password: form.find( "input[name = admin_password]" ).val(),

		// Options de connexion.
		//  Note : conversion explicite de la valeur booléenne en valeur entière.
		secure_only: form.find( "input[id = secure_only]" ).is( ":checked" ) && 0,
		auto_connect: form.find( "input[id = auto_connect]" ).is( ":checked" ) && 0

	} )
		.done( ( data ) =>
		{
			// On affiche la notification de confirmation.
			addQueuedNotification( data, 3 );
		} )
		.fail( ( self ) =>
		{
			// Dans le cas contraire, on affiche un message d'erreur.
			addQueuedNotification( self.responseText, 1 );
		} );

	// On réinitialise enfin le formulaire.
	$( event.target ).parent()[ 0 ].reset();
} );