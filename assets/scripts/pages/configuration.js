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
$( "form" ).on( "submit", ( event ) =>
{
	// On cesse d'abord le comportement par défaut.
	event.preventDefault();

	// On réalise ensuite la requête AJAX.
	$.post( "includes/controllers/server_storage.php", {

		// Action qui doit être réalisée (insertion, mise à jour ou connexion).
		ftp_action: $( event.target ).data( "action" ),

		// Adresse IP du serveur FTP.
		ftp_address: $( "[name = ftp_address]" ).val(),

		// Port de communication du serveur FTP.
		ftp_port: $( "[name = ftp_port]" ).val(),

		// Protocole de transmission du serveur FTP.
		ftp_protocol: $( "[name = ftp_protocol] option:checked" ).val(),

		// Nom d'utilisateur du serveur FTP.
		ftp_user: $( "[name = ftp_user]" ).val(),

		// Mot de passe du serveur FTP.
		ftp_password: $( "[name = ftp_password]" ).val()

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