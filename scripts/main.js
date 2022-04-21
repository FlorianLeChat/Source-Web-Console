//
// Permet d'afficher des messages d'avertissement lorsqu'un utilisateur
//	entre un mot de passe avec les majuscules activées.
// 	Source : https://www.w3schools.com/howto/howto_js_detect_capslock.asp
//
$( "input[type = password]" ).keyup( function ( event )
{
	if ( event.originalEvent.getModifierState( "CapsLock" ) )
	{
		// Si les majuscules sont activées, on insère dynamiquement
		//	un nouvel élément HTML après le champ de saisie.
		$( this ).next().after( `<p class=\"capslock\">${ capslock_enabled }</p>` );
	}
	else
	{
		// Dans le cas contraire, on le supprime.
		$( "p[class = capslock" ).remove();
	}
} );

//
// Permet de vérifier l'état de vérification actuel des champs
//	de saisies obligatoire d'un formulaire.
//	Note : cette vérification se fait AVANT TOUTES les autres.
//
$( "form" ).submit( function ( event )
{
	// On filtre tous les éléments nécessitant une valeur.
	const elements = $( this ).children().filter( "[required]" );

	for ( const element of elements )
	{
		// On vérifie alors l'état de vérification HTML.
		if ( !element.validity.valid )
		{
			event.preventDefault();
			event.stopImmediatePropagation();
		}
	}
} );

//
// Permet de vérifier les informations obligatoires dans les formulaires.
//
$( "*[required]" ).keyup( function ()
{
	// On récupère le message d'erreur présent par défaut.
	const element = $( this );
	const error = element.parent().find( ".error" );

	// On vérifie par la suite si l'élément est valide ou non
	//	aux yeux des vérifications HTML.
	if ( !element[ 0 ].validity.valid )
	{
		// On récupère alors le libellé du champ de saisie.
		//	Note : il doit se trouver techniquement juste avant le champ.
		let label = element.prev().html();

		if ( label == "" )
		{
			// S'il est invalide, on récupère tous les éléments précédents
			//	et on fait un recherche jusqu'à trouver un libellé.
			label = element.prevAll().filter( "label" ).html();
		}

		// On supprime ensuite les astérisque présents dans certains
		//	libellés qui définissent si le champ est obligatoire.
		label = label.replace( "*", "" );

		// On remplace les informations pré-formattées dans le message
		//	d'erreur par certaines données du champ de saisie.
		let message = client_check_failed;
		message = message.replace( "$1", label );							// Nom du champ.
		message = message.replace( "$2", element.attr( "minLength" ) );		// Taille minimale.
		message = message.replace( "$3", element.attr( "maxLength" ) );		// Taille maximale.

		// On définit enfin le message d'erreur avant de l'afficher
		//	progressivement avec une animation.
		error.html( message );
		error.fadeIn( 200 );
	}
	else
	{
		// Dans le cas contraire, on le fait disparaître.
		error.fadeOut( 150 );
	}
} );

//
// Permet d'ouvrir le formulaire de contact via le pied de page.
//
$( "footer" ).find( "a[href = \"javascript:void(0);\"]" ).click( function ()
{
	contact.fadeIn( 150 );
} );

//
// Permet de désactiver le mécanisme de glissement des liens.
//
$( "a" ).mousedown( function ( event )
{
	event.preventDefault();
} );

//
// Permet d'indiquer la position de défilement actuelle de l'utilisateur.
// 	Source : https://www.w3schools.com/howto/howto_js_scroll_indicator.asp
//
$( window ).scroll( function ()
{
	// Récupération de la racine du document.
	const root = $( document.documentElement );

	// Calcul de la position actuelle du défilement.
	const position = $( window ).scrollTop() || $( "body" ).scrollTop();
	const height = root.prop( "scrollHeight" ) - root.prop( "clientHeight" );

	// Calcul du pourcentage du décalage avant affichage.
	const offset = ( position / height ) * 100;

	$( "footer div" ).css( "width", offset + "%" );
} );

//
// Permet de gérer les mécanismes du formulaire de contact.
//
const contact = $( "#contact" );

contact.find( "form" ).submit( function ( event )
{
	// On cesse d'abord le comportement par défaut.
	event.preventDefault();

	// On réalise ensuite la requête AJAX.
	$.post( "includes/controllers/user_contact.php", {

		// Adresse électronique.
		email: contact.find( "input[name = email]" ).val(),

		// Sujet du message.
		subject: contact.find( "option:selected" ).text(),

		// Contenu du message.
		content: contact.find( "textarea" ).val()

	} )
		.done( function ( data, _status, _self )
		{
			// Une fois terminée, on affiche la réponse JSON du
			//	serveur sous forme d'une liste numérique.
			const json = JSON.parse( data );

			// On affiche alors un message de confirmation.
			addQueuedNotification( json[ 0 ], json[ 1 ] );

			// On réinitialise enfin l'entièreté du formulaire
			//	avant de le fermer si le message renvoyé par
			//	le serveur est un message de succès.
			if ( json[ 1 ] == 2 )
			{
				contact.find( "form" )[ 0 ].reset();
				contact.fadeOut( 150 );
			}
		} )
		.fail( function ( self, _status, error )
		{
			// Dans le cas contraire, on affiche une notification
			//	d'échec avec les informations à notre disposition.
			addQueuedNotification( form_contact_failed.replace( "$1", getStatusText( error, self.status ) ), 1 )
		} );
} );

contact.find( "input[type = reset]" ).click( function ()
{
	// On cache le formulaire à la demande de l'utilisateur.
	contact.fadeOut( 150 );
} );

//
// Permet d'ajuster l'agrandissement des éléments par rapport au zoom
// 	du navigateur (fonctionne seulement pour l'amoindrissement).
//
function adjustZoom()
{
	const zoom = 100 / Math.round( window.devicePixelRatio * 100 );

	if ( zoom >= 1 )
	{
		$( "body" ).css( "zoom", zoom );
	}
}

adjustZoom();

$( window ).resize( adjustZoom );

//
// Permet d'afficher des notifications textuelles après une action.
// 	Source : https://www.w3schools.com/howto/howto_js_snackbar.asp
//
const notification = $( "#notifications" );
let messages_queue = {};
let counter = 1;

function addQueuedNotification( text, type )
{
	// On ajoute la notification dans une file d'attente
	//	afin d'être traitée les uns après les autres.
	messages_queue[ counter ] = [ text, type ];
	counter++;
}

function processNotification( text, type )
{
	// On vérifie tout d'abord si une notification est déjà
	//	actuellement visible.
	if ( notification.is( ":visible" ) )
	{
		return false;
	}

	// On apparaître ensuite le bloc avant de définir
	//	le texte passé en paramètre de la fonction.
	notification.find( "span" ).html( text );
	notification.addClass( "show" );

	// On récupère après l'icône associé au conteneur.
	const icon = notification.find( "i" );

	// On vérifie alors le type de notification.
	if ( type == 1 )
	{
		// Cette notification est une erreur.
		notification.addClass( "error" );
		icon.addClass( "bi-exclamation-octagon-fill" );
	}
	else if ( type == 2 )
	{
		// Cette notification est une validation.
		notification.addClass( "success" );
		icon.addClass( "bi-check-square-fill" );
	}
	else if ( type == 3 )
	{
		// Cette notification est une information.
		notification.addClass( "info" );
		icon.addClass( "bi-info-square-fill" );
	}

	setTimeout( function ()
	{
		// Après 5 secondes d'affichage, on supprime toutes
		//	les classes associées aux élements pour les faire
		//	disparaître progressivement.
		icon.removeAttr( "class" );
		notification.removeAttr( "class" );
	}, 5000 );

	// On retourne cette variable pour signifier à la file
	//	d'attente que la notification a été créée avec succès.
	return true;
}

setInterval( function ()
{
	// On récupère d'abord toutes les clés disponibles dans
	//	la file d'attente des notifications.
	const keys = Object.keys( messages_queue );

	// On vérifie alors si la file n'est pas vide avant de
	//	continuer son traitement.
	if ( keys.length > 0 )
	{
		// On récupère ensuite les données associées à la première
		//	notification de la file afin de la traiter.
		const notification = messages_queue[ keys[ 0 ] ];
		const state = processNotification( notification[ 0 ], notification[ 1 ] );

		if ( state )
		{
			// Si la notification a été créée, alors on supprime les
			//	données de la file d'attente pour la prochaine.
			delete messages_queue[ keys[ 0 ] ];
		}
	}
}, 500 )

//
// Permet de bloquer le renvoie des formulaires lors du rafraîchissement
//	de la page par l'utilisateur.
// 	Source : https://stackoverflow.com/a/45656609
//
if ( window.history.replaceState && window.location.hostname !== "localhost" )
{
	window.history.replaceState( null, null, window.location.href );
}

//
// Permet d'obtenir le texte de réponse adéquat en fonction du code HTTP.
//	Note : cette fonctionnalité est présente par défaut avec le protocole
//		HTTP/1.1 mais complètement abandonnée avec HTTP/2 et HTTP/3.
//	Sources : https://github.com/whatwg/fetch/issues/599 / https://fetch.spec.whatwg.org/#concept-response-status-message
//
function getStatusText( response, code )
{
	// On vérifie si la réponse originale n'est pas vide.
	//	Note : cela peut être le cas sur un serveur de développement
	//		mais aussi sur certains navigateurs comme Firefox.
	if ( response !== "" )
	{
		return response;
	}

	// Dans le cas contraire, on retourne manuellement une liste réduite
	//	de réponses en fonction du code actuel.
	//	Source : https://searchfox.org/mozilla-central/rev/a5102e7f8ec3cda922b7c012b732a1efaff0e732/netwerk/protocol/http/nsHttpResponseHead.cpp#340
	switch ( code )
	{
		case 200:
			return "OK";
		case 404:
			return "Not Found";
		case 301:
			return "Moved Permanently";
		case 307:
			return "Temporary Redirect";
		case 400:
			return "Bad Request";
		case 401:
			return "Unauthorized";
		case 402:
			return "Payment Required";
		case 403:
			return "Forbidden";
		case 405:
			return "Method Not Allowed";
		case 408:
			return "Request Timeout";
		case 429:
			return "Too Many Requests";
		case 500:
			return "Internal Server Error";
		default:
			return "No Reason Phrase";
	}
}

//
// Permet d'ajouter le mécanisme de fonctionnement de Google Analytics.
// 	Source : https://analytics.google.com/analytics/web/#/
//
window.dataLayer = window.dataLayer || [];

function gtag()
{
	dataLayer.push( arguments );
}

gtag( "js", new Date() );
gtag( "config", "G-56KCE1D8JG" );