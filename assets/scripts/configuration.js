// Importation des feuilles de style.
import "../styles/desktop/configuration.scss";
import "../styles/phone/configuration.scss";
import "../styles/tablet/configuration.scss";

//
// Permet d'enregistrer ou de mettre à jour les données du
//	serveur de stockage FTP.
//
$( "form input[type = submit]" ).click( ( event ) =>
{
	// On cesse d'abord le comportement par défaut.
	event.preventDefault();

	// On réalise ensuite la requête AJAX.
	$.post( "includes/controllers/server_storage.php", {

		// Action qui doit être réalisée (insertion, mise à jour ou connexion).
		ftp_action: $( event.target ).attr( "data-action" ),

		// Adresse IP du serveur FTP.
		ftp_address: $( "input[name = ftp_address]" ).val(),

		// Port de communication du serveur FTP.
		ftp_port: $( "input[name = ftp_port]" ).val(),

		// Protocole de transmission du serveur FTP.
		ftp_protocol: $( "select[name = ftp_protocol] option:checked" ).val(),

		// Nom d'utilisateur du serveur FTP.
		ftp_user: $( "input[name = ftp_user]" ).val(),

		// Mot de passe du serveur FTP.
		ftp_password: $( "input[name = ftp_password]" ).val()

	} )
		.done( ( data, _status, _self ) =>
		{
			// Une fois terminée, on affiche la notification d'information
			//	à l'utilisateur pour lui indiquer si la requête a été envoyée
			//	ou non avec succès au serveur distant.
			if ( data !== "" )
			{
				addQueuedNotification( data, 3 );
			}
		} )
		.fail( ( self, _status, error ) =>
		{
			// Dans le cas contraire, on affiche une notification
			//	d'échec avec les informations à notre disposition.
			addQueuedNotification( server_fatal_error.replace( "$1", getStatusText( error, self.status ) ), 1 );
		} );
} );

//
// Permet de mettre à jour les informations présentes dans le fichier
//	de configuration du serveur distant.
//
$( "button[data-type]" ).click( ( event ) =>
{
	// On cesse d'abord le comportement par défaut.
	event.preventDefault();

	// On réalise ensuite la requête AJAX.
	$.post( "includes/controllers/server_storage.php", {

		// Action qui doit être réalisée (insertion, mise à jour ou connexion).
		ftp_action: "connexion",

		// Type de modification qui doivent être effectué.
		ftp_type: $( event.target ).attr( "data-type" ),

		// Valeur indiquée par l'utilisateur.
		ftp_value: $( event.target ).prev().val()

	} )
		.done( ( data, _status, _self ) =>
		{
			// Une fois terminée, on affiche la notification d'information
			//	à l'utilisateur pour lui indiquer si la requête a été envoyée
			//	ou non avec succès au serveur distant.
			if ( data !== "" )
			{
				addQueuedNotification( data, 3 );
			}
		} )
		.fail( ( self, _status, error ) =>
		{
			// Dans le cas contraire, on affiche une notification
			//	d'échec avec les informations à notre disposition.
			addQueuedNotification( server_fatal_error.replace( "$1", getStatusText( error, self.status ) ), 1 );
		} );
} );