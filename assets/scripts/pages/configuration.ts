// Importation de la feuille de style.
import "../../styles/desktop/configuration.scss";

// Importation des fonctions et constantes communes.
import "../global";
import { addQueuedNotification } from "../functions";

//
// Permet d'enregistrer ou de mettre à jour les données du
//  serveur de stockage FTP.
//
const storage = $( "#storage" );

$( "form" ).on( "submit", async ( event ) =>
{
	// On cesse d'abord le comportement par défaut.
	event.preventDefault();

	// On bloque également le bouton de soumission
	//  pour éviter les abus.
	storage.find( "[type = submit]" ).prop( "disabled", true );

	// On réalise ensuite la requête AJAX.
	const response = await fetch( storage.data( "route" ), {
		method: "POST",
		headers: {
			"Content-Type": "application/x-www-form-urlencoded"
		},
		body: new URLSearchParams( {
			// Jeton de sécurité (CSRF).
			token: storage.data( "token" ),

			// Adresse IP du serveur FTP.
			address: $( "[name = address]" ).val() as never,

			// Port de communication du serveur FTP.
			port: $( "[name = port]" ).val() as never,

			// Protocole de transmission du serveur FTP.
			protocol: $( "[name = protocol] option:checked" ).val() as never,

			// Nom d'utilisateur du serveur FTP.
			username: $( "[name = username]" ).val() as never,

			// Mot de passe du serveur FTP.
			password: $( "[name = password]" ).val() as never
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
		storage.find( "[type = submit]" ).prop( "disabled", false );
	}
} );

//
// Permet de mettre à jour les informations présentes dans le fichier
//  de configuration du serveur distant.
//
const parameters = $( "#parameters" );

$( "[data-type]" ).on( "click", async ( event ) =>
{
	// On cesse d'abord le comportement par défaut.
	event.preventDefault();

	// On récupère le chemin d'accès vers le fichier de
	//  configuration du serveur distant.
	const path = prompt( window.storage_path );

	if ( !path )
	{
		return;
	}

	// On bloque également le bouton de soumission
	//  pour éviter les abus.
	parameters.find( "[type = button]" ).prop( "disabled", true );

	// On réalise ensuite la requête AJAX.
	const target = $( event.target );
	const element = target.is( "i" ) ? target.parent() : target;
	const response = await fetch( parameters.data( "route" ), {
		method: "POST",
		headers: {
			"Content-Type": "application/x-www-form-urlencoded"
		},
		body: new URLSearchParams( {
			// Jeton de sécurité (CSRF).
			token: element.data( "token" ),

			// Type de modification qui doivent être effectué.
			type: element.data( "type" ),

			// Chemin d'accès vers le fichier de configuration.
			path,

			// Valeur indiquée par l'utilisateur.
			value: element.prev().val() as never
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
		parameters.find( "[type = button]" ).prop( "disabled", false );
	}
} );