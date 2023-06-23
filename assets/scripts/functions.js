//
// Permet d'afficher des notifications textuelles après une action.
//  Source : https://www.w3schools.com/howto/howto_js_snackbar.asp
//
const messageQueue = {};
let counter = 1;

export function addQueuedNotification( text, type )
{
	// On ajoute la notification dans une file d'attente
	//  afin d'être traitée les uns après les autres.
	messageQueue[ counter ] = [ text.substring( 0, 500 ), type ];
	counter++;
}

function processNotification( text, type )
{
	// On vérifie tout d'abord si une notification est déjà
	//  actuellement visible.
	const notification = $( "#notifications" );

	if ( notification.is( ":visible" ) )
	{
		return false;
	}

	// On apparaître ensuite le bloc avant de définir
	//  le texte passé en paramètre de la fonction.
	notification.find( "span" ).text( text );
	notification.addClass( "show" );

	// On récupère après l'icône associé au conteneur.
	const icon = notification.find( "i" );

	// On vérifie alors le type de notification.
	if ( type === 1 )
	{
		// Cette notification est une erreur.
		notification.addClass( "error" );
		icon.addClass( "bi-exclamation-octagon-fill" );
	}
	else if ( type === 2 )
	{
		// Cette notification est une validation.
		notification.addClass( "success" );
		icon.addClass( "bi-check-square-fill" );
	}
	else if ( type === 3 )
	{
		// Cette notification est une information.
		notification.addClass( "info" );
		icon.addClass( "bi-info-square-fill" );
	}

	setTimeout( () =>
	{
		// Après 5 secondes d'affichage, on supprime toutes
		//  les classes associées aux éléments pour les faire
		//  disparaître progressivement.
		icon.removeAttr( "class" );
		notification.removeAttr( "class" );
	}, 5000 );

	// On retourne cette variable pour signifier à la file
	//  d'attente que la notification a été créée avec succès.
	return true;
}

setInterval( () =>
{
	// On récupère d'abord toutes les clés disponibles dans
	//  la file d'attente des notifications.
	const keys = Object.keys( messageQueue );

	// On vérifie alors si la file n'est pas vide avant de
	//  continuer son traitement.
	if ( keys.length > 0 )
	{
		// On récupère ensuite les données associées à la première
		//  notification de la file afin de la traiter.
		const notification = messageQueue[ keys[ 0 ] ];
		const state = processNotification( notification[ 0 ], notification[ 1 ] );

		if ( state )
		{
			// Si la notification a été créée, alors on supprime les
			//  données de la file d'attente pour la prochaine.
			delete messageQueue[ keys[ 0 ] ];
		}
	}
}, 500 );

//
// Permet d'envoyer les commandes et actions vers un serveur distant.
//
export function sendRemoteAction( action, value )
{
	// On réalise d'abord la requête AJAX.
	$.post( "includes/controllers/server_actions.php", {

		// Action qui doit être réalisée à distance.
		server_action: action,

		// Valeur possiblement associée à une commande.
		server_value: value

	} )
		.done( ( data ) =>
		{
			// Une fois terminée, on affiche la notification d'information
			//  à l'utilisateur pour lui indiquer si la requête a été envoyée
			//  ou non avec succès au serveur distant.
			if ( data !== "" )
			{
				addQueuedNotification( data, 3 );
			}
		} )
		.fail( ( self ) =>
		{
			// Dans le cas contraire, on affiche un message d'erreur.
			addQueuedNotification( self.responseText, 1 );
		} );
}