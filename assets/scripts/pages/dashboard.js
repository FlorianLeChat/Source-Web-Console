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
	const image = $( server ).attr( "data-image" );

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
let submitEdit = false;

$( "[name = server_edit]" ).on( "click", ( event ) =>
{
	// On cesse d'abord le comportement par défaut.
	if ( submitEdit )
	{
		return;
	}

	// On cesse le comportement par défaut.
	event.preventDefault();

	// On récupère le parent de l'élément.
	const parent = $( event.target ).parent();

	// On demande ensuite à l'utilisateur s'il veut supprimer ou non
	//  le serveur.
	if ( confirm( edit_remove ) )
	{
		// Suppression de l'action par défaut.
		$( "input[value = edit]" ).remove();

		// Ajout de l'action de suppression.
		parent.append( "<input type=\"hidden\" name=\"server_action\" value=\"delete\" />" );
	}
	else
	{
		// Adresse IP, port et mot de passe du serveur.
		parent.append( `<input type="hidden" name="address" value="${ prompt( edit_address ) }" />` );
		parent.append( `<input type="hidden" name="port" value="${ prompt( edit_port ) }" />` );
		parent.append( `<input type="hidden" name="password" value="${ prompt( edit_password ) }" />` );
	}

	// On force enfin la soumission du formulaire en indiquant
	//  qu'on ne doit pas demander de nouveau les informations.
	submitEdit = true;

	$( event.target ).trigger( "click" );
} );

//
// Permet de faire la récupération des informations générales du serveur.
//
let timer;

async function retrieveRemoteData()
{
	// On réalise d'abord la requête AJAX.
	const response = await fetch( "api/server/monitor" );

	// On vérifie ensuite si la requête a été effectuée avec succès.
	if ( response.ok )
	{
		// Une fois terminée, on récupère les données sous format JSON.
		const data = await response.json();

		// Affichage de l'état de fonctionnement.
		$( "[data-field = state]" ).text( data.state );

		// Affichage de la carte actuelle.
		$( "[data-field = map]" ).text( data.map );

		// Affichage du nombre de joueurs/clients.
		$( "[data-field = players]" ).text( data.count );

		// Affichage de la liste des joueurs.
		const players = $( "#players ul" );
		players.empty();

		data.players.forEach( ( player ) =>
		{
			players.append( `<li>[${ player.index }] ${ player.name }</li>` );
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
$( "#actions li" ).on( "click", ( event ) =>
{
	// Requête classique en fonction du bouton.
	sendRemoteAction( $( event.target ).attr( "data-action" ) );
} );

$( "#actions li:first-of-type" ).on( "dblclick", () =>
{
	// Requête d'arrêt forcé.
	sendRemoteAction( "force" );
} );