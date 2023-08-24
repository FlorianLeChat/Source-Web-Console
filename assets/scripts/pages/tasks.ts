// Importation de la feuille de style.
import "../../styles/desktop/tasks.scss";

// Importation des fonctions et constantes communes.
import "../global";
import { addQueuedNotification } from "../functions";

//
// Permet d'envoyer les demandes de création de tâches planifiées
//  vers la base de données.
//
const tasks = $( "#tasks" );

$( "form" ).on( "submit", async ( event ) =>
{
	// On cesse d'abord le comportement par défaut.
	event.preventDefault();

	// On bloque également le bouton de soumission
	//  pour éviter les abus.
	tasks.find( "[type = submit]" ).prop( "disabled", true );

	// On réalise ensuite la requête AJAX.
	const response = await fetch( tasks.data( "add-route" ), {
		method: "POST",
		headers: {
			"Content-Type": "application/x-www-form-urlencoded"
		},
		body: new URLSearchParams( {
			// Jeton de sécurité (CSRF).
			token: tasks.data( "token" ),

			// Date de déclenchement de l'action (format horodatage).
			date: $( "[name = date]" ).val() as never,

			// Identifiant unique du serveur sélectionné.
			server: $( "[name = server] option:checked" ).data( "server" ) as never,

			// Nom de l'action qui doit être réalisé.
			action: $( "[name = action]" ).val() as never
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
		tasks.find( "[type = submit]" ).prop( "disabled", false );
	}
} );

//
// Permet de gérer les demandes de suppression de tâches planifiées
//  dans la base de données.
//
$( "tbody tr:not([class = finished])" ).on( "click", async ( event ) =>
{
	// On vérifie si l'utilisateur demande a supprimer la tâche.
	if ( confirm( window.edit_remove ) )
	{
		// On réalise ensuite la requête AJAX.
		const target = $( event.target ).parent();
		const response = await fetch( tasks.data( "remove-route" ), {
			method: "POST",
			headers: {
				"Content-Type": "application/x-www-form-urlencoded"
			},
			body: new URLSearchParams( {
				// Jeton de sécurité (CSRF).
				token: tasks.data( "token" ),

				// Identifiant unique de la tâche sélectionnée.
				task: target.data( "task" ),

				// Identifiant unique du serveur sélectionné.
				server: target.find( "em" ).data( "server" )
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
	}
} );