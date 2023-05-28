// Importation des fonctions et constantes communes.
import "./cookies";
import "./analytics";
import { addQueuedNotification, getStatusText } from "./functions";

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
		$( event.target ).next().after( `<p class="capslock">${ capslock_enabled }</p>` );
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

		if ( label === "" )
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
		message = message.replace( "$1", label ); // Nom du champ.
		message = message.replace( "$2", element.attr( "minLength" ) ); // Taille minimale.
		message = message.replace( "$3", element.attr( "maxLength" ) ); // Taille maximale.

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
const contact = $( "#contact" );

$( "footer" ).find( "a[href = \"javascript:void(0);\"]" ).on( "click", () =>
{
	contact.fadeIn( 150 );
} );

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

	settings.xhr = () =>
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
		.done( ( data ) =>
		{
			// Une fois terminée, on affiche la message de confirmation.
			addQueuedNotification( data.message, data.code );

			// On réinitialise enfin l'entièreté du formulaire
			//  avant de le fermer si le message renvoyé par
			//  le serveur est un message de succès.
			if ( data.code === 2 )
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
const pages = {};
const search = $( "#search input[name = search]" );

$( "nav span, footer a[href *= target] span" ).each( ( _, page ) =>
{
	// Libellés des pages de la barre de navigation ainsi que ceux
	//  présents dans le pied de page.
	pages[ $( page ).html() ] = $( page ).parent().attr( "href" );
} );

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
	Object.keys( pages ).forEach( ( page ) =>
	{
		// Si l'entrée n'est pas vide et qu'elle semble correspondre
		//  à une page mise en mémoire, on l'ajoute en tant que résultat.
		if ( content !== "" && page.toLowerCase().match( content.toLowerCase() ) )
		{
			results.append( `<li data-target="${ pages[ page ] }">${ page }</li>` );
		}
	} );
} );

$( "#search ul" ).on( "click", "li", ( event ) =>
{
	// On simule la présence d'un élément <a> en JavaScript.
	window.location.href = $( event.target ).attr( "data-target" );
} );