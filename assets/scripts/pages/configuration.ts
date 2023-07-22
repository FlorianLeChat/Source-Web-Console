// Importation des feuilles de style.
import "../../styles/desktop/configuration.scss";
import "../../styles/phone/configuration.scss";
import "../../styles/tablet/configuration.scss";

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

			// Action qui doit être réalisée (insertion, mise à jour ou connexion).
			action: storage.data( "action" ),

			// Adresse IP du serveur FTP.
			address: $( "[name = address]" ).val() as string,

			// Port de communication du serveur FTP.
			port: $( "[name = port]" ).val() as string,

			// Protocole de transmission du serveur FTP.
			protocol: $( "[name = protocol] option:checked" ).val() as string,

			// Nom d'utilisateur du serveur FTP.
			username: $( "[name = username]" ).val() as string,

			// Mot de passe du serveur FTP.
			password: $( "[name = password]" ).val() as string
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
$( "[data-type]" ).on( "click", ( event ) =>
{
	// On cesse d'abord le comportement par défaut.
	event.preventDefault();

	// On réalise ensuite la requête AJAX.
	const target = $( event.target );

	$.post( "includes/controllers/server_storage.php", {

		// Action qui doit être réalisée (insertion, mise à jour ou connexion).
		ftp_action: "connexion",

		// Type de modification qui doivent être effectué.
		ftp_type: target.data( "type" ),

		// Valeur indiquée par l'utilisateur.
		ftp_value: target.prev().val()

	} )
		.done( ( data ) =>
		{
			// Une fois terminée, on affiche la notification d'information
			//  à l'utilisateur pour lui indiquer si la requête a été envoyée
			//  ou non avec succès au serveur distant.
			if ( data )
			{
				addQueuedNotification( data, 3 );
			}
		} )
		.fail( ( self ) =>
		{
			// Dans le cas contraire, on affiche un message d'erreur.
			addQueuedNotification( self.responseText, 1 );
		} );
} );