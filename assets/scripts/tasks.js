// Importation des feuilles de style.
import "../styles/desktop/tasks.scss";
import "../styles/phone/tasks.scss";
import "../styles/tablet/tasks.scss";

// Importation des fonctions et constantes communes.
import { addQueuedNotification, getStatusText } from "./_functions";

//
// Permet de gérer les demandes de suppression de tâches planifiées
//  dans la base de données.
//
$( "table tr:not([class = finished])" ).on( "click", ( event ) =>
{
	// On vérifie si l'utilisateur demande a supprimer la tâche.
	if ( confirm( edit_remove ) )
	{
		// On réalise ensuite la requête AJAX.
		$.post( "includes/controllers/server_tasks.php", {

			// Identifiant unique du serveur sélectionné.
			target_server: $( event.target ).find( "em" ).attr( "data-server" ),

			// Identifiant unique de la tâche sélectionnée.
			target_task: $( event.target ).attr( "data-task" )

		} )
			.done( ( data ) =>
			{
				// On affiche la notification de confirmation.
				addQueuedNotification( data, 3 );

				// On recharge enfin la page quelques instants après.
				//  Note : cela pourrait être amélioré en construisant directement
				//   le HTML à la réception finale du message.
				setTimeout( () =>
				{
					window.location.reload();
				}, 3000 );
			} )
			.fail( ( self, _status, error ) =>
			{
				// Dans le cas contraire, on affiche une notification
				//  d'échec avec les informations à notre disposition.
				addQueuedNotification( server_fatal_error.replace( "$1", getStatusText( error, self.status ) ), 1 );
			} );
	}
} );

//
// Permet d'envoyer les demandes de création de tâches planifiées
//  vers la base de données.
//
$( "form input[type = submit]" ).on( "click", ( event ) =>
{
	// On cesse d'abord le comportement par défaut.
	event.preventDefault();

	// On réalise ensuite la requête AJAX.
	$.post( "includes/controllers/server_tasks.php", {

		// Identifiant unique du serveur sélectionné.
		target_server: $( "select[name = server] option:checked" ).attr( "data-server" ),

		// Date de déclenchement de l'action (format horodatage).
		trigger_date: $( "input[name = date]" ).val(),

		// Nom de l'action qui doit être réalisé.
		trigger_action: $( "select[name = action]" ).val()

	} )
		.done( ( data ) =>
		{
			// On affiche la notification de confirmation.
			addQueuedNotification( data, 3 );

			// On recharge enfin la page quelques instants après.
			//  Note : cela pourrait être amélioré en construisant directement
			//   le HTML à la réception finale du message.
			setTimeout( () =>
			{
				window.location.reload();
			}, 3000 );
		} )
		.fail( ( self, _status, error ) =>
		{
			// Dans le cas contraire, on affiche une notification
			//  d'échec avec les informations à notre disposition.
			addQueuedNotification( server_fatal_error.replace( "$1", getStatusText( error, self.status ) ), 1 );
		} );
} );