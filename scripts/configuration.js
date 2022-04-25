//
// Permet d'enregistrer ou de mettre à jour les données du
//	serveur de stockage FTP.
//
$( "form input[type = submit]" ).click( function ( event )
{
	// On cesse d'abord le comportement par défaut.
	event.preventDefault();

	// On réalise ensuite la requête AJAX.
	$.post( "includes/controllers/server_storage.php", {

		// Action qui doit être réalisée (insertion ou mise à jour).
		ftp_action: $( this ).attr( "data-action" ),

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
		.done( function ( data, _status, _self )
		{
			console.log( data );
		} )
		.fail( function ( self, _status, error )
		{
			// Dans le cas contraire, on affiche une notification
			//	d'échec avec les informations à notre disposition.
			addQueuedNotification( server_fatal_error.replace( "$1", getStatusText( error, self.status ) ), 1 );
		} );
} );