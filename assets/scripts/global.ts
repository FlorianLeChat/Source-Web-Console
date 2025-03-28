// Importation du normalisateur TypeScript.
import "@total-typescript/ts-reset";

// Importation des fonctions et constantes communes.
import "./cookies";
import "./analytics";
import { addQueuedNotification } from "./functions";

// Déclaration du contexte global du navigateur.
declare global
{
	interface Window
	{
		// Méthode fetch avec prise en charge de reCAPTCHA.
		proxyFetch: typeof fetch;

		// Déclaration des traductions injectées par Twig.
		edit_port: string;
		edit_remove: string;
		edit_address: string;
		edit_password: string;

		utc_time: string;
		cpu_usage: string;
		fps_usage: string;
		tick_rate: string;
		storage_path: string;
		player_count: string;
		onetime_info: string;
		oauth_success: string;
		execute_value: string;
		usage_percent: string;
		capslock_enabled: string;
		analytics_identifier: string;

		recover_password_username: string;
		recover_password_password: string;

		recaptcha_error: string;
		recaptcha_public_key: string;

		command_add_title: string;
		command_add_content: string;

		// Déclaration des données des statistiques des serveurs.
		time_data: string[];
		cpu_usage_data: number[];
		tick_rate_data: number[];
		player_count_data: number[];

		// Déclaration des données de l'API Google Analytics.
		dataLayer?: ( string | Date )[];
	}
}

//
// Permet d'afficher des messages d'avertissement lorsqu'un utilisateur
//  entre un mot de passe avec les majuscules activées.
//  Source : https://www.w3schools.com/howto/howto_js_detect_capslock.asp
//
$( "input[type = password]" ).on( "keyup", ( event ) =>
{
	if ( event.originalEvent?.getModifierState( "CapsLock" ) )
	{
		// Si les majuscules sont activées, on insère dynamiquement
		//  un nouvel élément HTML après le champ de saisie.
		$( event.target )
			.next()
			.after( `<p class="capslock">${ window.capslock_enabled }</p>` );
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
$( "[required]" ).on( "input", ( event ) =>
{
	// On récupère le message d'erreur présent par défaut.
	const element = event.target as HTMLInputElement;
	const error = $( element ).siblings( ".error" );

	// On vérifie par la suite si l'élément est valide ou non
	//  aux yeux des vérifications HTML.
	if ( !element.checkValidity() )
	{
		// On définit enfin le message d'erreur avant de l'afficher
		//  progressivement avec une animation.
		error.html( element.validationMessage );
		error.fadeIn( 200 ).css( "display", "block" );
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

$( "footer" ).on( "click", "a[href = \"#\"]", ( event ) =>
{
	event.preventDefault();

	contact.fadeIn( 150 );
} );

//
// Permet de générer un jeton d'authentification pour les services
//  de Google reCAPTCHA lors de l'envoi d'un formulaire au travers
//  de l'API Fetch ou via une soumission classique.
//
if ( process.env.RECAPTCHA_ENABLED === "true" )
{
	window.proxyFetch = async ( url, options ) =>
	{
		// On vérifie d'abord si la requête est une requête issue
		//  d'un formulaire quelconque.
		if ( options && options.method !== "GET" )
		{
			// On vérifie si les services de reCAPTCHA sont disponibles.
			if ( typeof window.grecaptcha === "undefined" )
			{
				addQueuedNotification( window.recaptcha_error, 1 );
				return Promise.reject( new Error( window.recaptcha_error ) );
			}

			// On génère alors une nouvelle promesse qui attendra
			//  que le jeton de vérification soit récupéré.
			const token = new Promise<string>( ( resolve ) =>
			{
				// On attend ensuite que les services de reCAPTCHA soient chargés.
				window.grecaptcha.ready( async () =>
				{
					// Une fois terminé, on exécute après une requête de vérification
					//  afin d'obtenir un jeton de vérification auprès de Google.
					resolve(
						await window.grecaptcha.execute(
							window.recaptcha_public_key,
							{ action: "submit" }
						)
					);
				} );
			} );

			// On ajoute le jeton de vérification à la requête.
			options.body = ( options.body ?? new FormData() ) as FormData;
			options.body.append( "recaptcha", await token );
		}

		// On retourne enfin la requête originale.
		return fetch( url, options );
	};

	$( "form[method = POST]" ).on( "submit", ( event ) =>
	{
		// On cesse d'abord le comportement par défaut du formulaire.
		event.preventDefault();

		// On vérifie si les services de reCAPTCHA sont disponibles.
		if ( typeof window.grecaptcha === "undefined" )
		{
			addQueuedNotification( window.recaptcha_error, 1 );
			return;
		}

		// On supprime l'événement pour éviter de recommencer une nouvelle
		//  fois ce processus.
		$( "form[method = POST]" ).off();

		// On attend ensuite que les services de reCAPTCHA soient chargés.
		window.grecaptcha.ready( async () =>
		{
			// Une fois terminé, on exécute après une requête de vérification
			//  afin d'obtenir un jeton de vérification auprès de Google.
			const token = await window.grecaptcha.execute(
				window.recaptcha_public_key,
				{ action: "submit" }
			);

			// On insère alors dynamiquement le jeton dans le formulaire.
			const target = $( event.target );
			target.append(
				`<input type="hidden" name="recaptcha" value="${ token }">`
			);

			// On clique enfin sur le bouton de soumission du formulaire
			//  ou on le soumet directement si l'événement n'est pas issu
			//  d'un clic sur un bouton.
			if ( event.originalEvent )
			{
				$(
					( event.originalEvent as SubmitEvent )
						.submitter as HTMLFormElement
				).trigger( "click" );
			}
			else
			{
				target.trigger( "submit" );
			}
		} );
	} );
}
else
{
	// Utilisation de la méthode originale si reCAPTCHA n'est pas activé.
	window.proxyFetch = fetch;
}

//
// Permet d'indiquer la position de défilement actuelle de l'utilisateur.
//  Source : https://www.w3schools.com/howto/howto_js_scroll_indicator.asp
//
$( window ).on( "scroll", () =>
{
	// Récupération de la racine du document.
	const root = $( document.documentElement );

	// Calcul de la position actuelle du défilement.
	const position = $( window ).scrollTop() ?? $( "body" ).scrollTop();
	const height = root.prop( "scrollHeight" ) - root.prop( "clientHeight" );

	// Calcul du pourcentage du décalage avant affichage.
	if ( position )
	{
		$( "footer div > div" ).width( `${ ( position / height ) * 100 }%` );
	}
} );

//
// Permet de gérer les mécanismes du formulaire de contact.
//
contact.on( "submit", "form", async ( event ) =>
{
	// On cesse d'abord le comportement par défaut.
	event.preventDefault();

	// On bloque également les boutons de soumission et
	//  de réinitialisation pour éviter les abus.
	contact.find( "[type = submit]" ).prop( "disabled", true );
	contact.find( "[type = reset]" ).prop( "disabled", true );

	// On réalise alors la requête AJAX.
	const response = await window.proxyFetch( contact.data( "route" ), {
		method: "POST",
		headers: {
			"Content-Type": "application/x-www-form-urlencoded"
		},
		body: new URLSearchParams( {
			// Jeton de sécurité (CSRF).
			token: contact.data( "token" ),

			// Adresse électronique.
			email: contact.find( "input[name = email]" ).val() as never,

			// Sujet du message.
			subject: contact.find( "option:selected" ).text() as never,

			// Contenu du message.
			content: contact.find( "textarea" ).val() as never
		} )
	} );

	// On affiche ensuite un message de confirmation ou d'erreur.
	addQueuedNotification( await response.text(), response.ok ? 2 : 1 );

	// On vérifie si la requête a été effectuée avec succès.
	if ( response.ok )
	{
		// Dans ce cas, on réinitialise l'entièreté du formulaire
		//  avant de le fermer.
		contact.find( "form" )[ 0 ].reset();
		contact.fadeOut( 150 );
	}
	else
	{
		// On libère enfin les boutons de soumission et
		//  de réinitialisation en cas d'erreur.
		contact.find( "[type = submit]" ).prop( "disabled", false );
		contact.find( "[type = reset]" ).prop( "disabled", false );
	}
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
const pages: Record<string, string> = {};
const search = $( "#search input[name = search]" );

$( "nav span, footer a[href *= target] span" ).each( ( _, page ) =>
{
	// Libellés des pages de la barre de navigation ainsi que ceux
	//  présents dans le pied de page.
	const route = $( page );
	pages[ route.text() ] = route.parent().attr( "href" ) ?? "";
} );

search.on( "keyup", ( event ) =>
{
	// On récupère la recherche de l'utilisateur ainsi
	//  que la liste des résultats possibles.
	const target = $( event.target );
	const content = target.val() as string;
	const results = target.next();

	// On vide ensuite les résultats précédents.
	results.empty();

	// On itère alors à travers toutes les pages afin
	//  de les comparer à l'entrée de l'utilisateur.
	Object.keys( pages ).forEach( ( page ) =>
	{
		// Si l'entrée n'est pas vide et qu'elle semble correspondre
		//  à une page mise en mémoire, on l'ajoute en tant que résultat.
		if ( content && page.toLowerCase().includes( content.toLowerCase() ) )
		{
			results.append( `<option value="${ pages[ page ] }">${ page }</option>` );
		}
	} );
} );

search.on( "input", () =>
{
	// On récupère la recherche de l'utilisateur ainsi
	//  que la liste des résultats possibles.
	const value = search.val() as string;
	const options = search.next().children();

	options.each( ( _, option ) =>
	{
		// Si l'entrée de l'utilisateur correspond à une page
		//  mise en mémoire, on le redirige enfin vers celle-ci.
		if ( $( option ).attr( "value" ) === value )
		{
			window.location.href = value;
		}
	} );
} );