// Importation des feuilles de style.
import "../../styles/desktop/dashboard.scss";
import "../../styles/phone/dashboard.scss";
import "../../styles/tablet/dashboard.scss";

// Importation des fonctions et constantes communes.
import "../global";
import { addQueuedNotification, sendRemoteAction, getStatusText } from "../functions";

//
// Permet d'appliquer les images en arrière-plan des serveurs
//  en fonction de leur jeu installé.
//
$( "li[data-image]" ).each( ( indice, server ) =>
{
	$( `<style>#servers li:nth-of-type(${ indice }):before { background-image: url(${ $( server ).attr( "data-image" ) })</style>` ).appendTo( "head" );
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

function retrieveRemoteData()
{
	// On réalise d'abord la requête AJAX.
	$.post( "includes/controllers/server_monitoring.php" )
		.done( ( data ) =>
		{
			// Une fois terminée, on affiche la réponse JSON du
			//  serveur sous forme d'une liste numérique.
			const json = JSON.parse( data );

			// On vérifie ensuite s'il ne s'agit pas d'une erreur,
			//  dans ce cas, on affiche une notification avant de
			//  casser définitivement le minuteur.
			if ( json.hasOwnProperty( "error" ) )
			{
				clearInterval( timer );
				addQueuedNotification( self.responseText.replace( "$1", json.error ), 1 );
				return;
			}

			// Affichage de l'état de fonctionnement.
			const stateField = $( "[data-field = state]" );

			if ( json.hasOwnProperty( "gamemode" ) )
			{
				// Vérification de l'état (maintenance ou en fonctionnement).
				if ( json.password === true )
				{
					// Serveur sécurisé par mot de passe, maintenance ou mise à jour en cours.
					stateField.html( server_service.replace( "$1", json.gamemode ) );
				}
				else
				{
					// Serveur en fonctionnement standard.
					stateField.html( server_running.replace( "$1", json.gamemode ) );
				}
			}
			else
			{
				// Information par défaut.
				stateField.html( server_no_data );
			}

			// Affichage de la carte actuelle.
			const mapsField = $( "[data-field = map]" );

			if ( json.hasOwnProperty( "maps" ) )
			{
				// Information du serveur.
				mapsField.html( json.maps );
			}
			else
			{
				// Information par défaut.
				mapsField.html( "gm_source" );
			}

			// Affichage du nombre de joueurs/clients.
			const countField = $( "[data-field = players]" );

			if ( json.hasOwnProperty( "players" ) && json.hasOwnProperty( "max_players" ) && json.hasOwnProperty( "bots" ) )
			{
				// Information du serveur.
				countField.html( `${ json.players } / ${ json.max_players } [${ json.bots }]` );
			}
			else
			{
				// Information par défaut.
				countField.html( "0 / 0 [0]" );
			}

			// Affichage de la liste des joueurs.
			const playerList = $( "#players ul" );
			playerList.empty();

			json.playerList.forEach( ( player ) =>
			{
				playerList.append( `<li>[${ player.index }] ${ player.name }</li>` );
			} );
		} )
		.fail( ( self, _status, error ) =>
		{
			// Dans le cas contraire, on affiche une notification
			//  d'échec avec les informations à notre disposition
			//  avant de casser définitivement le minuteur.
			clearInterval( timer );

			if ( self.status !== 400 )
			{
				addQueuedNotification( self.responseText.replace( "$1", getStatusText( error, self.status ) ), 1 );
			}
		} );
}

// Récupération des informations au démarrage.
retrieveRemoteData();

// Récupération des informations toutes les 5 secondes.
timer = setInterval( () =>
{
	retrieveRemoteData();
}, 5000 );

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