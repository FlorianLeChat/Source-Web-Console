//
// Permet d'afficher des notifications textuelles après une action.
//  Source : https://www.w3schools.com/howto/howto_js_snackbar.asp
//
const notifications = $( "#notifications" );
const messageQueue: Record<number, [ string, number ]> = {};
let isInBounds = false;
let counter = 1;
let timer: NodeJS.Timeout | undefined;

export function addQueuedNotification( text: string, type: number )
{
	// On ajoute la notification dans une file d'attente
	//  afin d'être traitée les uns après les autres.
	messageQueue[ counter ] = [ text.substring( 0, 500 ), type ];
	counter++;
}

function processNotification( text: string, type: number )
{
	// On vérifie tout d'abord si une notification est déjà
	//  actuellement visible.
	if ( notifications.is( ":visible" ) )
	{
		return false;
	}

	// On apparaître ensuite le bloc avant de définir
	//  le texte passé en paramètre de la fonction.
	notifications.find( "span" ).text( text );
	notifications.addClass( "show" );

	// On récupère après l'icône associé au conteneur.
	const icon = notifications.find( "i" );

	// On vérifie alors le type de notification.
	if ( type === 1 )
	{
		// Cette notification est une erreur.
		notifications.addClass( "error" );
		icon.addClass( "bi-exclamation-octagon-fill" );
	}
	else if ( type === 2 )
	{
		// Cette notification est une validation.
		notifications.addClass( "success" );
		icon.addClass( "bi-check-square-fill" );
	}
	else if ( type === 3 )
	{
		// Cette notification est une information.
		notifications.addClass( "info" );
		icon.addClass( "bi-info-square-fill" );
	}

	setInterval( () =>
	{
		// On vérifie si la souris est actuellement dans la zone
		//  de la notification pour ne pas la supprimer.
		if ( isInBounds )
		{
			// Si c'est le cas, alors on supprime le minuteur
			//  pour ne pas supprimer la notification.
			if ( timer )
			{
				clearTimeout( timer );
				timer = undefined;
			}

			return;
		}

		// Dans le cas contraire, on attend 5 secondes avant
		//  de supprimer la notification avec une animation.
		if ( !timer )
		{
			timer = setTimeout( () =>
			{
				// Déclenchement de l'animation de sortie.
				notifications.addClass( "hide" );

				setTimeout( () =>
				{
					// Suppression de la notification.
					icon.removeAttr( "class" );
					notifications.removeAttr( "class" );

					// Suppression du minuteur.
					clearTimeout( timer );
					timer = undefined;
				}, 250 );
			}, 4750 );
		}
	}, 500 );

	// On retourne enfin cette variable pour signifier à la file
	//  d'attente que la notification a été créée avec succès.
	return true;
}

notifications.on( "mouseenter", () =>
{
	// Entrée de la souris dans la zone de la notification.
	isInBounds = true;
} );

notifications.on( "mouseleave", () =>
{
	// Sortie de la souris de la zone de la notification.
	isInBounds = false;
} );

setInterval( () =>
{
	// On récupère d'abord toutes les clés disponibles dans
	//  la file d'attente des notifications.
	const keys: number[] = Object.keys( messageQueue ).map( ( key ) => parseInt( key, 10 ) );

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
export async function sendRemoteAction( token: string, route: string, action: string, value = "" )
{
	// On réalise d'abord la requête AJAX.
	const response = await fetch( route, {
		method: "POST",
		headers: {
			"Content-Type": "application/x-www-form-urlencoded"
		},
		body: new URLSearchParams( {
			// Jeton de sécurité (CSRF).
			token,

			// Action qui doit être réalisée à distance.
			action,

			// Valeur possiblement associée à une commande.
			value
		} )
	} );

	// On affiche enfin un message de confirmation ou d'erreur
	//  si nécessaire en fonction du résultat de la requête.
	const text = await response.text();

	if ( text )
	{
		addQueuedNotification( text, response.ok ? 3 : 1 );
	}
}