// Dépendances CSS/JavaScript.
import "normalize.css/normalize.css";
import "flag-icons/css/flag-icons.min.css";
import "bootstrap-icons/font/bootstrap-icons.css";
import "vanilla-cookieconsent/dist/cookieconsent.css";

//
// Permet d'afficher des messages d'avertissement lorsqu'un utilisateur
//  entre un mot de passe avec les majuscules activées.
//  Source : https://www.w3schools.com/howto/howto_js_detect_capslock.asp
//
$( "input[type = password]" ).on( "keyup", ( event ) =>
{
	if ( event.originalEvent.getModifierState( "CapsLock" ) )
	{
		// Si les majuscules sont activées, on insère dynamiquement
		//  un nouvel élément HTML après le champ de saisie.
		$( event.target ).next().after( `<p class=\"capslock\">${ capslock_enabled }</p>` );
	}
	else
	{
		// Dans le cas contraire, on le supprime.
		$( "p[class = capslock" ).remove();
	}
} );

//
// Permet de vérifier les informations obligatoires dans les formulaires.
//
$( "*[required]" ).on( "keyup", ( event ) =>
{
	// On récupère le message d'erreur présent par défaut.
	const element = $( event.target );
	const error = element.parent().find( ".error" );

	// On vérifie par la suite si l'élément est valide ou non
	//  aux yeux des vérifications HTML.
	if ( !element[ 0 ].validity.valid )
	{
		// On récupère alors le libellé du champ de saisie.
		//  Note : il doit se trouver techniquement juste avant le champ.
		let label = element.prev().html();

		if ( label == "" )
		{
			// S'il est invalide, on récupère tous les éléments précédents
			//  et on fait un recherche jusqu'à trouver un libellé.
			label = element.prevAll().filter( "label" ).html();
		}

		// On supprime ensuite les astérisques présents dans certains
		//  libellés qui définissent si le champ est obligatoire.
		label = label.replaceAll( "*", "" );

		// On remplace les informations pré-formatées dans le message
		//  d'erreur par certaines données du champ de saisie.
		let message = client_check_failed;
		message = message.replace( "$1", label );							// Nom du champ.
		message = message.replace( "$2", element.attr( "minLength" ) );		// Taille minimale.
		message = message.replace( "$3", element.attr( "maxLength" ) );		// Taille maximale.

		// On définit enfin le message d'erreur avant de l'afficher
		//  progressivement avec une animation.
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
$( "footer" ).find( "a[href = \"javascript:void(0);\"]" ).on( "click", () =>
{
	contact.fadeIn( 150 );
} );

//
// Permet de désactiver le mécanisme de glissement des liens.
//
$( "a" ).on( "mousedown", ( event ) =>
{
	event.preventDefault();
} );

//
// Permet de retarder l'ensemble des requêtes asynchrones AJAX
//  afin d'inclure un jeton d'authentification généré par les services
//  de Google reCAPTCHA.
//
$( window ).ajaxSend( ( _event, _request, settings ) =>
{
	// On vérifie d'abord si la requête est de type POST.
	//  Note : seules les requêtes de soumission doivent être surveillées.
	if ( settings.type !== "POST" )
	{
		return;
	}

	// On met en mémoire la fonction de retour utilisée par la requête.
	const callback = settings.xhr;

	settings.xhr = function ()
	{
		// On récupère par la même occasion certaines données de la requête.
		const request = callback();
		const sender = request.send;

		// On redéfinit après la fonction de retour de la requête.
		request.send = ( ...parameters ) =>
		{
			// On attend ensuite que les services de reCAPTCHA soient chargés.
			grecaptcha.ready( async () =>
			{
				// Une fois terminé, on exécute alors une requête de vérification
				//  afin d'obtenir un jeton de vérification auprès de Google.
				const token = await grecaptcha.execute( captcha_public_key );

				// On ajoute par la suite le jeton aux paramètres de la requête.
				parameters[ 0 ] += `&recaptcha=${ token }`;

				// On vérifie l'état de la requête avant de l'exécuter de nouveau
				//  avec les paramètres modifiés.
				if ( request.readyState === 1 )
				{
					sender.apply( request, parameters );
				}
			} );
		};

		// On retourne enfin la requête modifiée.
		return request;
	};
} );

//
// Permet d'indiquer la position de défilement actuelle de l'utilisateur.
//  Source : https://www.w3schools.com/howto/howto_js_scroll_indicator.asp
//
$( window ).on( "scroll", () =>
{
	// Récupération de la racine du document.
	const root = $( document.documentElement );

	// Calcul de la position actuelle du défilement.
	const position = $( window ).scrollTop() || $( "body" ).scrollTop();
	const height = root.prop( "scrollHeight" ) - root.prop( "clientHeight" );

	// Calcul du pourcentage du décalage avant affichage.
	const offset = ( position / height ) * 100;

	$( "footer div > div" ).css( "width", `${ offset }%` );
} );

//
// Permet de gérer les mécanismes du formulaire de contact.
//
const contact = $( "#contact" );

contact.find( "form" ).on( "submit", ( event ) =>
{
	// On cesse d'abord le comportement par défaut.
	event.preventDefault();

	// On réalise ensuite la requête AJAX.
	$.post( "api/user/contact", {

		// Adresse électronique.
		email: contact.find( "input[name = email]" ).val(),

		// Sujet du message.
		subject: contact.find( "option:selected" ).text(),

		// Contenu du message.
		content: contact.find( "textarea" ).val()

	} )
		.done( ( data, _status, _self ) =>
		{
			// Une fois terminée, on affiche la réponse JSON du
			//  serveur sous forme d'une liste numérique.
			const json = JSON.parse( data );

			// On affiche alors un message de confirmation.
			addQueuedNotification( json[ 0 ], json[ 1 ] );

			// On réinitialise enfin l'entièreté du formulaire
			//  avant de le fermer si le message renvoyé par
			//  le serveur est un message de succès.
			if ( json[ 1 ] == 2 )
			{
				contact.find( "form" )[ 0 ].reset();
				contact.fadeOut( 150 );
			}
		} )
		.fail( ( self, _status, error ) =>
		{
			// Dans le cas contraire, on affiche une notification
			//  d'échec avec les informations à notre disposition.
			addQueuedNotification( form_contact_failed.replace( "$1", getStatusText( error, self.status ) ), 1 );
		} );
} );

contact.find( "input[type = reset]" ).on( "click", () =>
{
	// On cache le formulaire à la demande de l'utilisateur.
	contact.fadeOut( 150 );
} );

//
// Permet de faire fonctionner un petit moteur de recherche
//  intégré pour accéder plus rapidement aux pages du site.
//
const search = $( "#search input[name = search]" );
let pages = {};

for ( const page of $( "nav span, footer a[href *= target] span" ) )
{
	// Libellés des pages de la barre de navigation ainsi que ceux
	//  présents dans le pied de page.
	pages[ $( page ).html() ] = $( page ).parent().attr( "href" );
}

search.on( "focusout", () =>
{
	// La définition de l'opacité est une astuce qui permet aux
	//  événements "click" de jQuery de pouvoir s'exécuter systématiquement
	//  lorsque les résultats doivent être cachés.
	search.next().css( "opacity", 0 );
} );

search.on( "focusin", () =>
{
	// Voir commentaire précédent.
	search.next().css( "opacity", 1 );
} );

search.on( "keyup", ( event ) =>
{
	// On récupère la recherche de l'utilisateur ainsi
	//  que la liste des résultats possibles.
	const content = $( event.target ).val();
	const results = $( event.target ).next();

	// On vide ensuite les résultats précédents.
	results.empty();

	// On itère alors à travers toutes les pages afin
	//  de les comparer à l'entrée de l'utilisateur.
	for ( const page of Object.keys( pages ) )
	{
		// Si l'entrée n'est pas vide et qu'elle semble correspondre
		//  à une page mise en mémoire, on l'ajoute en tant que résultat.
		if ( content !== "" && page.toLowerCase().match( content.toLowerCase() ) )
		{
			results.append( `<li data-target=\"${ pages[ page ] }\">${ page }</li>` );
		}
	}
} );

$( "#search ul" ).on( "click", "li", ( event ) =>
{
	// On simule la présence d'un élément <a> en JavaScript.
	window.location.href = $( event.target ).attr( "data-target" );
} );

//
// Permet d'ajuster l'agrandissement des éléments par rapport au zoom
//  du navigateur (fonctionne seulement pour l'amoindrissement).
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

$( window ).on( "resize", adjustZoom );

//
// Permet d'afficher des notifications textuelles après une action.
//  Source : https://www.w3schools.com/howto/howto_js_snackbar.asp
//
const notification = $( "#notifications" );
let messages_queue = {};
let counter = 1;

function addQueuedNotification( text, type )
{
	// On ajoute la notification dans une file d'attente
	//  afin d'être traitée les uns après les autres.
	messages_queue[ counter ] = [ text, type ];
	counter++;
}

function processNotification( text, type )
{
	// On vérifie tout d'abord si une notification est déjà
	//  actuellement visible.
	if ( notification.is( ":visible" ) )
	{
		return false;
	}

	// On apparaître ensuite le bloc avant de définir
	//  le texte passé en paramètre de la fonction.
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
	const keys = Object.keys( messages_queue );

	// On vérifie alors si la file n'est pas vide avant de
	//  continuer son traitement.
	if ( keys.length > 0 )
	{
		// On récupère ensuite les données associées à la première
		//  notification de la file afin de la traiter.
		const notification = messages_queue[ keys[ 0 ] ];
		const state = processNotification( notification[ 0 ], notification[ 1 ] );

		if ( state )
		{
			// Si la notification a été créée, alors on supprime les
			//  données de la file d'attente pour la prochaine.
			delete messages_queue[ keys[ 0 ] ];
		}
	}
}, 500 );

//
// Permet de bloquer le renvoie des formulaires lors du rafraîchissement
//  de la page par l'utilisateur.
//  Source : https://stackoverflow.com/a/45656609
//
if ( window.history.replaceState && window.location.hostname !== "localhost" )
{
	window.history.replaceState( null, null, window.location.href );
}

//
// Permet d'envoyer les commandes et actions vers un serveur distant.
//
function sendRemoteAction( action, value )
{
	// On réalise d'abord la requête AJAX.
	$.post( "includes/controllers/server_actions.php", {

		// Action qui doit être réalisée à distance.
		server_action: action,

		// Valeur possiblement associée à une commande.
		server_value: value

	} )
		.done( ( data, _status, _self ) =>
		{
			// Une fois terminée, on affiche la notification d'information
			//  à l'utilisateur pour lui indiquer si la requête a été envoyée
			//  ou non avec succès au serveur distant.
			if ( data !== "" )
			{
				addQueuedNotification( data, 3 );
			}
		} )
		.fail( ( self, _status, error ) =>
		{
			// Dans le cas contraire, on affiche une notification
			//  d'échec avec les informations à notre disposition.
			addQueuedNotification( server_fatal_error.replace( "$1", getStatusText( error, self.status ) ), 1 );
		} );
}

//
// Permet d'obtenir le texte de réponse adéquat en fonction du code HTTP.
//  Note : cette fonctionnalité est présente par défaut avec le protocole
//   HTTP/1.1 mais complètement abandonnée avec HTTP/2 et HTTP/3.
//  Sources : https://github.com/whatwg/fetch/issues/599 / https://fetch.spec.whatwg.org/#concept-response-status-message
//
function getStatusText( response, code )
{
	// On vérifie si la réponse originale n'est pas vide.
	//  Note : cela peut être le cas sur un serveur de développement
	//   mais aussi sur certains navigateurs comme Firefox.
	if ( response !== "" )
	{
		return response;
	}

	// Dans le cas contraire, on retourne manuellement une liste réduite
	//  de réponses en fonction du code actuel.
	//  Source : https://searchfox.org/mozilla-central/rev/a5102e7f8ec3cda922b7c012b732a1efaff0e732/netwerk/protocol/http/nsHttpResponseHead.cpp#340
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
//  Source : https://analytics.google.com/analytics/web/#/
//
function sendAnalytics()
{
	window.dataLayer = window.dataLayer || [];

	function gtag()
	{
		dataLayer.push( arguments );
	}

	gtag( "js", new Date() );
	gtag( "config", analytics_identifier );
}

//
// Permet d'implémenter le chargement différé de certaines ressources (images, vidéos, ...).
//  Source : https://github.com/verlok/vanilla-lazyload#-getting-started---script
//
import LazyLoad from "vanilla-lazyload";

const resources = $( "img, video, [data-bg]" );
resources.addClass( "lazy" );

const lazyLoad = new LazyLoad();
lazyLoad.update();

//
// Permet de demander le consentement de l'utilisateur pour utiliser le mécanisme des cookies.
//  Note : ne s'applique pas à la page des mentions légales.
//  Source : https://github.com/orestbida/cookieconsent#all-configuration-options
//
import "vanilla-cookieconsent";

if ( window.location.search !== "?target=legal" )
{
	// On initialise le mécanisme de consentement.
	const cookie = initCookieConsent();

	// On force l'utilisation du thème sombre pour la fenêtre.
	$( "body" ).addClass( "c_darkmode" );

	// On exécute alors le mécanisme en suivant certaines consignes.
	cookie.run( {
		// On définit d'abord nos paramètres personnalisés.
		page_scripts: false,		// Désactivation de la gestion des scripts
		force_consent: true,		// Le consentement est obligatoire.
		auto_language: "document",	// Langue sélectionnée par l'utilisateur.
		cookie_expiration: 31,		// Temps d'expiration du cookie (en jours).

		onAccept: ( cookie ) =>
		{
			// Lors de chaque chargement de page, on itère à travers toutes les
			//  autorisations pour déterminer si les balises de signalement de
			//  Google Analytics doivent être utilisées.
			for ( const level of cookie.categories )
			{
				if ( level === "analytics" )
				{
					sendAnalytics();
					break;
				}
			}
		},

		// On définit enfin les traductions globales pour la bibliothèque.
		languages: {
			// Traductions anglaises.
			"EN": {
				consent_modal: {
					// Première partie de la fenêtre.
					title: "Do you want a cookie?",
					description: "Hello, this website uses essential cookies to ensure its proper functioning and tracking cookies to understand how you interact with it. The latter will only be set with your consent. <button type=\"button\" data-cc=\"c-settings\" class=\"cc-link\">Please let me choose</button>.",
					primary_btn: {
						// Bouton pour accepter tous les cookies.
						text: "Accept all",
						role: "accept_all"
					},
					secondary_btn: {
						// Boutons pour refuser tous les cookies.
						text: "Refuse all",
						role: "accept_necessary"
					}
				},
				settings_modal: {
					// Seconde partie de la fenêtre.
					title: "Cookie preferences",
					save_settings_btn: "Save settings",
					accept_all_btn: "Accept all",
					reject_all_btn: "Refuse all",
					close_btn_label: "Fermer",
					cookie_table_headers: [
						// Format de l'en-tête des cookies.
						{ col1: "Nom" },
						{ col2: "Domaine" },
						{ col3: "Description" },
					],
					blocks: [
						{
							// En-tête de la fenêtre.
							title: "Cookie Usage 📢",
							description: "I use cookies to provide basic website functionality and to enhance your online experience. For each category, you can choose to accept or decline cookies whenever you want. For more details about cookies and other sensitive data, please read the full <a href=\"?target=legal\" class=\"cc-link\">privacy policy</a>.",
						},
						{
							// Première option.
							title: "Strictly necessary cookies",
							description: "These cookies are essential to the proper functioning of the website. Without these cookies, the site would not function properly.",
							toggle: {
								value: "necessary",
								enabled: true,
								readonly: true
							}
						},
						{
							// Deuxième option.
							title: "Performance and analysis cookies",
							description: "These cookies allow the website to remember choices you have made in the past.",
							toggle: {
								value: "analytics",
								enabled: false,
								readonly: false
							},
							cookie_table: [
								{
									col1: "^_ga",
									col2: "analytics.google.com",
									col3: "Date and time the page was loaded.",
									is_regex: true
								},
								{
									col1: "_gid",
									col2: "analytics.google.com",
									col3: "Unique identifier associated with the current website."
								}
							]
						}
					]
				}
			},

			// Traductions françaises.
			"FR": {
				consent_modal: {
					// Première partie de la fenêtre.
					title: "Vous voulez un cookie ?",
					description: "Bonjour, ce site Internet utilise des cookies essentiels pour assurer son bon fonctionnement et des cookies de suivi pour comprendre comment vous interagissez avec lui. Ces derniers ne seront mis en place qu'après consentement. <button type=\"button\" data-cc=\"c-settings\" class=\"cc-link\">Laissez moi choisir</button>.",
					primary_btn: {
						// Bouton pour accepter tous les cookies.
						text: "Tout accepter",
						role: "accept_all"
					},
					secondary_btn: {
						// Boutons pour refuser tous les cookies.
						text: "Tout refuser",
						role: "accept_necessary"
					}
				},
				settings_modal: {
					// Seconde partie de la fenêtre.
					title: "Préférences en matière de cookies",
					save_settings_btn: "Sauvegarder les paramètres",
					accept_all_btn: "Tout accepter",
					reject_all_btn: "Tout refuser",
					close_btn_label: "Fermer",
					cookie_table_headers: [
						// Format de l'en-tête des cookies.
						{ col1: "Nom" },
						{ col2: "Domaine" },
						{ col3: "Description" },
					],
					blocks: [
						{
							// En-tête de la fenêtre.
							title: "Utilisation des cookies 📢",
							description: "J'utilise des cookies pour assurer les fonctionnalités de base du site Internet et pour améliorer votre expérience en ligne. Pour chaque catégorie, vous pouvez choisir d'accepter ou de refuser les cookies quand vous le souhaitez. Pour plus de détails relatifs aux cookies et autres données sensibles, veuillez lire l'intégralité de la rubrique <a href=\"?target=legal\" class=\"cc-link\">politique de confidentialité</a>.",
						},
						{
							// Première option.
							title: "Cookies strictement nécessaires",
							description: "Ces cookies sont essentiels au bon fonctionnement du site Internet. Sans ces cookies, le site ne fonctionnerait pas correctement.",
							toggle: {
								value: "necessary",
								enabled: true,
								readonly: true
							}
						},
						{
							// Deuxième option.
							title: "Cookies de performance et d'analyse",
							description: "Ces cookies permettent au site Internet de se souvenir des choix que vous avez faits dans le passé.",
							toggle: {
								value: "analytics",
								enabled: false,
								readonly: false
							},
							cookie_table: [
								{
									col1: "^_ga",
									col2: "analytics.google.com",
									col3: "Date et heure de chargement de la page.",
									is_regex: true
								},
								{
									col1: "_gid",
									col2: "analytics.google.com",
									col3: "Identifiant unique associé au site Internet actuel."
								}
							]
						}
					]
				}
			}
		}
	} );
}