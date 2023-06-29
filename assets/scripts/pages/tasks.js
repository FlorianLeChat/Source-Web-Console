// Importation des feuilles de style.
import "../../styles/desktop/tasks.scss";
import "../../styles/phone/tasks.scss";
import "../../styles/tablet/tasks.scss";

// Importation des fonctions et constantes communes.
import "../global";
import { addQueuedNotification } from "../functions";

//
// Permet d'envoyer les demandes de création de tâches planifiées
//  vers la base de données.
//
$( "form input[type = submit]" ).on( "click", async ( event ) =>
{
	// On cesse d'abord le comportement par défaut.
	event.preventDefault();

	// On réalise ensuite la requête AJAX.
	const section = $( "#tasks" );
	const response = await fetch( section.attr( "data-add-route" ), {
		method: "POST",
		headers: {
			"Content-Type": "application/x-www-form-urlencoded"
		},
		body: new URLSearchParams( {
			// Jeton de sécurité (CSRF).
			token: section.find( "input[name = token]" ).val(),

			// Date de déclenchement de l'action (format horodatage).
			date: $( "input[name = date]" ).val(),

			// Identifiant unique du serveur sélectionné.
			server: $( "select[name = server] option:checked" ).attr( "data-server" ),

			// Nom de l'action qui doit être réalisé.
			action: $( "select[name = action]" ).val()
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
} );

//
// Permet de gérer les demandes de suppression de tâches planifiées
//  dans la base de données.
//
$( "table tr:not([class = finished])" ).on( "click", async ( event ) =>
{
	// On vérifie si l'utilisateur demande a supprimer la tâche.
	if ( confirm( edit_remove ) )
	{
		// On réalise ensuite la requête AJAX.
		const target = $( event.target ).parent();
		const section = $( "#tasks" );
		const response = await fetch( section.attr( "data-remove-route" ), {
			method: "POST",
			headers: {
				"Content-Type": "application/x-www-form-urlencoded"
			},
			body: new URLSearchParams( {
				// Jeton de sécurité (CSRF).
				token: section.find( "input[name = token]" ).val(),

				// Identifiant unique de la tâche sélectionnée.
				task: target.attr( "data-task" ),

				// Identifiant unique du serveur sélectionné.
				server: target.find( "em" ).attr( "data-server" )
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