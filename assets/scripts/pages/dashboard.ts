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
	const parent = $( event.target ).parent();

	// On demande ensuite à l'utilisateur s'il veut supprimer ou non
	//  le serveur.
	if ( confirm( window.edit_remove ) )
	{
		// Ajout de l'action de suppression.
		parent.append( "<input type=\"hidden\" name=\"action\" value=\"delete\" />" );

		// Suppression du premier jeton CSRF (pour l'édition).
		parent.find( "[name = token]" ).first().remove();
	}
	else
	{
		// Adresse IP, port et mot de passe du serveur.
		const address = prompt( window.edit_address );
		const port = prompt( window.edit_port );
		const password = prompt( window.edit_password );

		if ( !address || !port || !password )
		{
			return;
		}

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

		// Suppression du dernier jeton CSRF (pour la suppression).
		parent.find( "[name = token]" ).last().remove();
	}

	// On force enfin la soumission du formulaire.
	parent.trigger( "click" );
} );

//
// Permet de défiler la liste des serveurs enregistrés.
//
const servers = $( "#servers" );

servers.on( "click", "button[type=button]", ( event ) =>
{
	// On récupère d'abord l'élément cible ainsi que l'élément
	//  qui contient le nombre de pages.
	const target = $( event.target );
	const label = servers.find( "> span" ).first();

	// On récupère ensuite le nombre de pages actuel ainsi que
	//  le nombre de pages maximum.
	let [ current, maximum ] = label.text().split( "/" ).map( ( num ) => parseInt( num, 10 ) );
	maximum = maximum || 1;

	// On modifie enfin le nombre de pages actuel en fonction
	//  de l'élément cible et on met à jour le nombre de pages
	//  actuel.
	current = target.hasClass( "bi bi-chevron-left" ) ? Math.max( 1, current - 1 ) : Math.min( maximum, current + 1 );

	label.text( `${ current } / ${ maximum }` );

	// On récupère enfin les éléments à afficher et on les affiche
	//  par groupe de 4.
	const elements = servers.find( "li" ).slice( ( current - 1 ) * 4, current * 4 );
	servers.find( "li:not(.hidden)" ).addClass( "hidden" );
	elements.removeClass( "hidden" );
} );

//
// Permet de faire la récupération des informations générales du serveur.
//
let timer: NodeJS.Timer | undefined;

async function retrieveRemoteData()
{
	// On réalise d'abord la requête AJAX.
	const response = await fetch( servers.data( "route" ) );

	// On vérifie ensuite si la requête a été effectuée avec succès.
	if ( response.ok )
	{
		// Une fois terminée, on récupère les données sous format JSON.
		const data = await response.json() as { state: string; map: string; count: number; players: { Name: string; }[]; };

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
		addQueuedNotification( await response.text(), 1 );

		clearInterval( timer );
		timer = undefined;
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

actions.on( "click", "li", async ( event ) =>
{
	// Requête classique d'action en fonction du bouton.
	const target = $( event.target );
	const action = target.data( "action" );

	if ( action )
	{
		// Blocage du bouton pour éviter les abus.
		target.addClass( "disabled" );

		// Exécution de la requête d'action.
		const state = await sendRemoteAction( target.data( "token" ), target.data( "route" ), target.data( "action" ) );

		if ( !state )
		{
			// Libération du bouton en cas d'erreur.
			target.removeClass( "disabled" );
		}
	}
} );

actions.on( "dblclick", "li:first-of-type", async ( event ) =>
{
	// Requête d'arrêt forcée
	const target = $( event.target );
	const parent = target.parent();

	// Blocage du bouton pour éviter les abus.
	parent.addClass( "disabled" );

	// Exécution de la requête d'action.
	const state = await sendRemoteAction( target.data( "token" ), target.data( "route" ), target.data( "action" ) );

	if ( !state )
	{
		// Libération du bouton en cas d'erreur.
		parent.removeClass( "disabled" );
	}
} );