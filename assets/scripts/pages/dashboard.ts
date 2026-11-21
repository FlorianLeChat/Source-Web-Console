// Importation de la feuille de style.
import "../../styles/desktop/dashboard.scss";

// Importation des fonctions et constantes communes.
import "../global";
import { openDialog, askConfirmation, executeQuickAction, addQueuedNotification } from "../functions";

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
$( "[name = server_edit]" ).on( "click", async ( event ) =>
{
	// On cesse d'abord le comportement par défaut.
	event.preventDefault();

	// On récupère après le bouton et le formulaire parent de l'élément.
	const button = $( event.currentTarget );
	const parent = button.closest( "form" );

	// On demande ensuite les nouvelles informations du serveur en
	//  proposant les valeurs actuelles (l'annulation arrête l'édition).
	const values = await openDialog( "", [
		{ name: "address", label: window.edit_address, value: String( button.data( "address" ) ) },
		{ name: "port", label: window.edit_port, type: "number", value: String( button.data( "port" ) ) },
		{ name: "password", label: window.edit_password, type: "password" }
	] );

	if ( values === null )
	{
		return;
	}

	// On ajoute alors les champs renseignés au formulaire.
	const fields = { address: values.address.trim(), port: values.port.trim(), password: values.password };

	Object.entries( fields ).forEach( ( [ name, value ] ) =>
	{
		if ( value )
		{
			$( "<input>", { type: "hidden", name, value } ).appendTo( parent );
		}
	} );

	// On force enfin la soumission du formulaire.
	parent.trigger( "submit" );
} );

$( "[name = server_delete]" ).on( "click", async ( event ) =>
{
	// On cesse d'abord le comportement par défaut.
	event.preventDefault();

	// On demande ensuite une confirmation explicite avant la
	//  suppression définitive du serveur.
	if ( await askConfirmation( window.edit_remove ) )
	{
		$( event.currentTarget ).closest( "form" ).trigger( "submit" );
	}
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
	let [ current, maximum ] = label
		.text()
		.split( "/" )
		.map( ( num ) => parseInt( num, 10 ) );
	maximum = maximum || 1;

	// On modifie enfin le nombre de pages actuel en fonction
	//  de l'élément cible et on met à jour le nombre de pages
	//  actuel.
	current = target.hasClass( "bi bi-chevron-left" )
		? Math.max( 1, current - 1 )
		: Math.min( maximum, current + 1 );

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
let timer: NodeJS.Timeout | undefined;

async function retrieveRemoteData()
{
	// On réalise d'abord la requête AJAX.
	const response = await window.proxyFetch( servers.data( "route" ) );

	// On vérifie ensuite si la requête a été effectuée avec succès.
	if ( response.ok )
	{
		// Une fois terminée, on récupère les données sous format JSON.
		const data = ( await response.json() ) as {
			map: string;
			state: string;
			count: number;
			players: { Name: string }[];
		};

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
				players.append( $( "<li>" ).text( name ) );
			}
		} );
	}
	else
	{
		// Dans le cas contraire, on affiche enfin un message d'erreur
		//  avant de supprimer le minuteur et de proposer une relance.
		addQueuedNotification( await response.text(), 1 );

		clearInterval( timer );
		timer = undefined;

		$( "[data-field = state]" ).text( window.monitor_interrupted );
		$( "#state [data-action = retry]" ).prop( "hidden", false );
	}
}

function startMonitoring()
{
	// Récupération des informations au démarrage.
	retrieveRemoteData();

	// Récupération des informations toutes les 3 secondes.
	timer = setInterval( () =>
	{
		retrieveRemoteData();
	}, 3000 );
}

startMonitoring();

$( "#state" ).on( "click", "[data-action = retry]", ( event ) =>
{
	// Relance de la surveillance à la demande de l'utilisateur.
	$( event.currentTarget ).prop( "hidden", true );
	startMonitoring();
} );

//
// Permet d'envoyer des requêtes d'action lors du clic sur l'un des
//  boutons du tableau de bord.
//
$( "#actions" ).on( "click", "button[data-action]", ( event ) =>
{
	executeQuickAction( $( event.currentTarget ) );
} );