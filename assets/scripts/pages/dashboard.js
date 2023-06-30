// Importation des feuilles de style.
import "../../styles/desktop/dashboard.scss";
import "../../styles/phone/dashboard.scss";
import "../../styles/tablet/dashboard.scss";

// Importation des fonctions et constantes communes.
import "../global";
import { addQueuedNotification, sendRemoteAction } from "../functions";

//
// Permet d'appliquer les images en arrière-plan des serveurs
//  en fonction de leur jeu installé.
//
$( "li[data-image]" ).each( ( indice, server ) =>
{
	const image = $( server ).data( "image" );

	if ( !image.endsWith( "0_background.webp" ) )
	{
		$( `
			<style>
				#servers li:nth-of-type(${ indice + 1 }):before
				{
					background-image: url(${ image });
				}
			</style>
		` ).appendTo( "head" );
	}
} );

//
// Permet d'effectuer des actions sur les serveurs présents
//  sur le tableau de bord.
//  Note : ce système peut largement être améliorable dans le futur.
//
$( "[name = server_edit]" ).one( "click", ( event ) =>
{
	// On cesse d'abord le comportement par défaut.
	event.preventDefault();

	// On récupère après le parent de l'élément.
	const target = $( event.target );
	const parent = target.parent();

	// On demande ensuite à l'utilisateur s'il veut supprimer ou non
	//  le serveur.
	if ( confirm( window.editRemove ) )
	{
		// Ajout de l'action de suppression.
		parent.append( "<input type=\"hidden\" name=\"action\" value=\"delete\" />" );
	}
	else
	{
		// Adresse IP, port et mot de passe du serveur.
		const address = prompt( window.edit_address );
		const port = prompt( window.edit_port );
		const password = prompt( window.edit_password );

		parent.append( "<input type=\"hidden\" name=\"action\" value=\"edit\" />" );

		if ( address )
		{
			// Adresse IP valide.
			parent.append( `<input type="hidden" name="address" value="${ address }" />` );
		}

		if ( port )
		{
			// Port valide.
			parent.append( `<input type="hidden" name="port" value="${ port }" />` );
		}

		if ( password )
		{
			// Mot de passe valide.
			parent.append( `<input type="hidden" name="password" value="${ password }" />` );
		}
	}

	// On force enfin la soumission du formulaire.
	target.trigger( "submit" );
} );

//
// Permet de faire la récupération des informations générales du serveur.
//
let timer;

async function retrieveRemoteData()
{
	// On réalise d'abord la requête AJAX.
	const response = await fetch( $( "#servers" ).data( "route" ) );

	// On vérifie ensuite si la requête a été effectuée avec succès.
	if ( response.ok )
	{
		// Une fois terminée, on récupère les données sous format JSON.
		const data = await response.json();

		// Affichage de l'état de fonctionnement.
		$( "[data-field = state]" ).html( data.state );

		// Affichage de la carte actuelle.
		$( "[data-field = map]" ).text( data.map );

		// Affichage du nombre de joueurs/clients.
		$( "[data-field = players]" ).text( data.count );

		// Affichage de la liste des joueurs.
		const players = $( "#players ul" );
		players.empty();

		data.players.forEach( ( player ) =>
		{
			const name = player.Name;

			if ( name )
			{
				players.append( `<li>${ name }</li>` );
			}
		} );
	}
	else
	{
		// Dans le cas contraire, on affiche enfin un message d'erreur
		//  avant de supprimer le minuteur.
		clearInterval( timer );
		addQueuedNotification( await response.text(), 1 );
	}
}

// Récupération des informations au démarrage.
retrieveRemoteData();

// Récupération des informations toutes les 3 secondes.
timer = setInterval( () =>
{
	retrieveRemoteData();
}, 3000 );

//
// Permet d'envoyer des requêtes d'action lors du clic sur l'un des
//  boutons du tableau de bord.
//
const actions = $( "#actions" );

actions.on( "click", "li", ( event ) =>
{
	// Requête classique d'action en fonction du bouton.
	const target = $( event.target );
	const action = target.data( "action" );

	if ( action )
	{
		sendRemoteAction( target.data( "token" ), target.data( "route" ), target.data( "action" ) );
	}
} );

actions.on( "dblclick", "li:first-of-type", ( event ) =>
{
	// Requête d'arrêt forcée
	const target = $( event.target );
	sendRemoteAction( target.data( "token" ), target.data( "route" ), target.data( "action" ) );
} );