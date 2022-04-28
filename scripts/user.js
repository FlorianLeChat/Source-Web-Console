//
// Permet d'envoyer les demandes de modification ou de suppression
//	des informations d'authentification vers le serveur.
//
$( "#account input" ).click( function ( event )
{
	// On cesse d'abord le comportement par défaut.
	event.preventDefault();

	// On vérifie après si l'utilisateur veut réellement supprimer
	//	son compte utilisateur.
	const action = $( this ).attr( "data-action" );

	if ( action === "remove" && !confirm( edit_remove ) )
	{
		return;
	}

	// On réalise ensuite la requête AJAX.
	$.post( "includes/controllers/server_user.php", {

		// Type de l'action qui doît être effectué.
		user_action: action,

		// Valeur du nouveau nom d'utilisateur.
		user_name: $( "input[name = username]" ).val(),

		// Valeur du nouveau mot de passe.
		user_password: $( "select[name = password]" ).val()

	} )
		.done( function ( data, _status, _self )
		{
			// On affiche la notification de confirmation.
			addQueuedNotification( data, 3 );
		} )
		.fail( function ( self, _status, error )
		{
			// Dans le cas contraire, on affiche une notification
			//	d'échec avec les informations à notre disposition.
			addQueuedNotification( server_fatal_error.replace( "$1", getStatusText( error, self.status ) ), 1 );
		} );

	// On réinitialise enfin le formulaire.
	$( this ).parent()[ 0 ].reset();
} );

//
// Permet d'envoyer les demandes de déconnexion et de reconnexion
//	au compte utilisateur.
//
$( "#actions input[type = submit]" ).click( function ( event )
{
	// On cesse d'abord le comportement par défaut.
	event.preventDefault();

	// On réalise ensuite la requête AJAX.
	$.post( "includes/controllers/server_user.php", {

		// Type de l'action qui doît être effectué
		user_action: $( this ).attr( "data-action" ),

	} )
		.done( function ( data, _status, _self )
		{
			// On affiche la notification de confirmation.
			addQueuedNotification( data, 3 );

			// On redirige l'utilisateur quelques instants après.
			setTimeout( function ()
			{
				window.location.href = "?target=index";
			}, 5000 );
		} )
		.fail( function ( self, _status, error )
		{
			// Dans le cas contraire, on affiche une notification
			//	d'échec avec les informations à notre disposition.
			addQueuedNotification( server_fatal_error.replace( "$1", getStatusText( error, self.status ) ), 1 );
		} );
} );

//
// Permet de modifier le comportement par défaut de la seconde
//	partie du formulaire d'inscription (oui c'est du recyclage).
//
$( "#signup input[type = submit]" ).attr( "data-action", "insert" );

//
// Permet d'envoyer les demandes d'ajout d'un nouveau serveur dans
//	la base de données.
//
$( "#signup input[type = submit]" ).click( function ( event )
{
	// On cesse d'abord le comportement par défaut.
	event.preventDefault();

	// On réalise ensuite la requête AJAX.
	const form = $( this ).parent();

	$.post( "includes/controllers/server_user.php", {

		// Type de l'action qui doît être effectué.
		user_action: $( this ).attr( "data-action" ),

		// Informations du client (obligatoires côté serveur).
		server_address: form.find( "input[name = server_address]" ).val(),
		server_port: form.find( "input[name = server_port]" ).val(),

		// Informations administrateur (facultatives).
		admin_address: form.find( "input[name = admin_address]" ).val(),
		admin_port: form.find( "input[name = admin_port]" ).val(),
		admin_password: form.find( "input[name = admin_password]" ).val(),

		// Options de connexion.
		//	Note : conversion explicite de la valeur booléenne en valeur entière.
		secure_only: form.find( "input[id = secure_only]" ).is( ":checked" ) | 0,
		auto_connect: form.find( "input[id = auto_connect]" ).is( ":checked" ) | 0

	} )
		.done( function ( data, _status, _self )
		{
			// On affiche la notification de confirmation.
			addQueuedNotification( data, 3 );
		} )
		.fail( function ( self, _status, error )
		{
			// Dans le cas contraire, on affiche une notification
			//	d'échec avec les informations à notre disposition.
			addQueuedNotification( server_fatal_error.replace( "$1", getStatusText( error, self.status ) ), 1 );
		} );

	// On réinitialise enfin le formulaire.
	$( this ).parent()[ 0 ].reset();
} );