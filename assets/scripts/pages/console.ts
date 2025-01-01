// Importation de la feuille de style.
import "../../styles/desktop/console.scss";

// Importation des fonctions et constantes communes.
import "../global";
import { sendRemoteAction, addQueuedNotification } from "../functions";

//
// Permet d'envoyer les entrées utilisateurs personnalisées
//  au serveur distant.
//
$( "#controller" ).on( "click", "button", async ( event ) =>
{
	// On récupère le contenu de l'entrée utilisateur avant
	//  de le vérifie pour la prochaine étape.
	const target = $( event.target );
	const element = target.is( "i" ) ? target.parent() : target;
	const input = element.prev().val() as string;

	if ( !input )
	{
		// C'est une chaîne vide.
		return;
	}

	// On bloque également le bouton pour éviter les abus.
	element.prop( "disabled", true );

	// On envoie ensuite le contenu au serveur distant.
	const state = await sendRemoteAction(
		element.data( "token" ),
		element.data( "route" ),
		"0",
		input
	);

	if ( state )
	{
		// Une fois réussie, on ajoute une entrée dans l'historique
		//  des entrées juste au-dessous.
		element.closest( "div" ).next().append( $( "<li></li>" ).text( input ) );
	}

	// On libère alors le bouton de soumission après l'envoi.
	element.prop( "disabled", false );

	// On réinitialise enfin le champ de saisie.
	element.prev().val( "" );
} );

//
// Permet de faire la récupération des informations générales du serveur.
//
const terminal = $( "#terminal" );
let timer: NodeJS.Timeout | undefined;

async function retrieveRemoteLogs()
{
	// On réalise d'abord la requête AJAX.
	const response = await fetch( terminal.data( "route" ) );

	// On vérifie ensuite si la requête a été effectuée avec succès.
	if ( response.ok )
	{
		// Une fois terminée, on récupère les données sous format JSON.
		const data = ( await response.json() ) as string[];

		// On affiche alors les journaux dans le terminal.
		const list = terminal.find( "ul" );
		list.empty();

		data.forEach( ( line ) =>
		{
			list.append( `<li>${ line }</li>` );
		} );

		list.scrollTop( list.prop( "scrollHeight" ) );
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

// Récupération des journaux au démarrage.
retrieveRemoteLogs();

// Récupération des journaux toutes les 3 secondes.
timer = setInterval( () =>
{
	retrieveRemoteLogs();
}, 3000 );