// Importation des feuilles de style.
import "../../styles/desktop/index.scss";
import "../../styles/phone/index.scss";
import "../../styles/tablet/index.scss";

// Importation des fonctions et constantes communes.
import "../global";
import { addQueuedNotification } from "../functions";

//
// Permet de gérer les ouvertures/fermetures de certains
//  formulaires de la page d'accueil.
//
const login = $( "#login" );
const header = $( "header li" );
const register = $( "#register article" );

header.first().on( "click", "button", () =>
{
	// Inscription (première partie).
	register.first().fadeIn( 150 );
} );

header.eq( 1 ).on( "click", "button", () =>
{
	// Connexion.
	login.fadeIn( 150 );
} );

//
// Permet de gérer les mécanismes du formulaire d'inscription.
//
const firstStep = register.first();
const lastStep = register.last();

register.on( "submit", "form", async ( event ) =>
{
	// On cesse d'abord le comportement par défaut.
	event.preventDefault();

	// On vérifie ensuite si l'utilisateur se trouve à la première
	//  ou à la deuxième étape de la phase d'inscription.
	if ( firstStep.is( ":visible" ) )
	{
		// Si c'est le cas, on passe à la seconde étape.
		firstStep.fadeOut( 150, () =>
		{
			lastStep.fadeIn( 150 );
		} );
	}
	else
	{
		// On bloque également les boutons de soumission et
		//  de réinitialisation pour éviter les abus.
		lastStep.find( "[type = submit]" ).prop( "disabled", true );
		lastStep.find( "[type = reset]" ).prop( "disabled", true );

		// Dans le cas contraire, on réalise alors une requête AJAX
		//  pour envoyer les informations au serveur.
		const parent = register.parent();
		const response = await fetch( parent.data( "route" ), {
			method: "POST",
			headers: {
				"Content-Type": "application/x-www-form-urlencoded"
			},
			body: new URLSearchParams( {
				// Jeton de sécurité (CSRF).
				token: parent.data( "token" ),

				// Nom d'utilisateur et mot de passe du compte utilisateur.
				username: firstStep.find( "[name = username]" ).val() as never,
				password: firstStep.find( "[name = password]" ).val() as never,

				// Option de maintien de connexion.
				_remember_me: firstStep.find( "[name = remember_me]" ).is( ":checked" ).toString(),

				// Informations du serveur.
				server_address: lastStep.find( "[name = address]" ).val() as never,
				server_port: lastStep.find( "[name = port]" ).val() as never,
				server_password: lastStep.find( "[name = password]" ).val() as never
			} )
		} );

		// On vérifie si la requête a été effectuée avec succès.
		if ( response.status === 202 )
		{
			// Si c'est un code HTTP 202, alors il s'agit d'une réponse
			//  suite à une demande de création d'un compte à usage unique.
			const data = await response.json() as { link: string, message: string; };

			navigator.clipboard.writeText( data.link ).then( () =>
			{
				addQueuedNotification( data.message, 3 );
			} );
		}
		else
		{
			// Dans l'autre cas (réussite avec code HTTP 201 ou erreur),
			//  on affiche après un message de confirmation ou d'erreur.
			addQueuedNotification( await response.text(), response.ok ? 2 : 1 );

			if ( response.ok )
			{
				// En cas de réussite, on réinitialise les deux formulaires
				//  avant de fermer le second.
				firstStep.find( "form" )[ 0 ].reset();
				lastStep.find( "form" )[ 0 ].reset();
				lastStep.fadeOut( 150 );

				// On effectue de suite la redirection de l'utilisateur
				//  vers le tableau de bord au bout de 5 secondes.
				setTimeout( () =>
				{
					window.location.href = parent.data( "redirect" );
				}, 3000 );
			}
			else
			{
				// On libère enfin les boutons de soumission et
				//  de réinitialisation en cas d'erreur.
				lastStep.find( "[type = submit]" ).prop( "disabled", false );
				lastStep.find( "[type = reset]" ).prop( "disabled", false );
			}
		}
	}
} );

register.on( "click", "[type = reset]", () =>
{
	// On vérifie d'abord si l'utilisateur se trouve ou non
	//  à la première étape de l'inscription.
	if ( firstStep.is( ":visible" ) )
	{
		// Si c'est le cas, on cache le formulaire..
		firstStep.fadeOut( 150 );

		// ..avant de réinitialiser les informations des deux parties.
		firstStep.find( "form" )[ 0 ].reset();
		firstStep.find( "input[id *= password]" ).attr( "type", "password" );
		lastStep.find( "form" )[ 0 ].reset();
	}
	else
	{
		// Dans le cas contraire, on retourne juste en arrière
		//  si l'utilisateur veut modifier certaines informations.
		firstStep.fadeIn( 150 );
		lastStep.fadeOut( 150 );
	}
} );

//
// Permet de gérer les mécanismes du formulaire de connexion.
//
login.on( "click", "[type = submit]", async ( event ) =>
{
	// On cesse d'abord le comportement par défaut.
	event.preventDefault();

	// On bloque également les boutons de soumission et
	//  de réinitialisation pour éviter les abus.
	login.find( "[type = submit]" ).prop( "disabled", true );
	login.find( "[type = reset]" ).prop( "disabled", true );

	// On réalise ensuite la requête AJAX.
	const response = await fetch( login.data( "route" ), {
		method: "POST",
		headers: {
			"Content-Type": "application/x-www-form-urlencoded"
		},
		body: new URLSearchParams( {
			// Jeton de sécurité (CSRF).
			token: login.data( "token" ),

			// Nom d'utilisateur.
			username: login.find( "[name = username]" ).val() as never,

			// Mot de passe.
			password: login.find( "[name = password]" ).val() as never,

			// Option de maintien de connexion.
			_remember_me: login.find( "[name = remember_me]" ).is( ":checked" ).toString()
		} )
	} );

	// On affiche après un message de confirmation ou d'erreur.
	addQueuedNotification( await response.text(), response.ok ? 2 : 1 );

	// On vérifie si la requête a été effectuée avec succès.
	if ( response.ok )
	{
		// Si c'est le cas, on réinitialise les deux formulaires
		//  avant de fermer le second.
		login.find( "form" )[ 0 ].reset();
		login.fadeOut( 150 );

		// On effectue alors la redirection de l'utilisateur
		//  vers le tableau de bord au bout de 5 secondes.
		setTimeout( () =>
		{
			window.location.href = login.data( "redirect" );
		}, 3000 );
	}
	else
	{
		// On libère enfin les boutons de soumission et
		//  de réinitialisation en cas d'erreur.
		login.find( "[type = submit]" ).prop( "disabled", false );
		login.find( "[type = reset]" ).prop( "disabled", false );
	}
} );

login.on( "click", "[type = reset]", () =>
{
	// On cache le formulaire à la demande de l'utilisateur.
	login.fadeOut( 150 );
} );

//
// Permet de gérer les liens de redirection présents dans le
//  formulaire de connexion.
//
const links = login.find( "a[href = \"javascript:void(0);\"]" );

links.first().on( "click", () =>
{
	login.fadeOut( 150, () =>
	{
		// Redirection vers l'inscription.
		register.first().fadeIn( 150 );
	} );
} );

links.eq( 1 ).on( "click", () =>
{
	login.fadeOut( 150, () =>
	{
		// Redirection vers la connexion unique.
		addQueuedNotification( window.onetime_info, 3 );

		register.last().fadeIn( 150 );
	} );
} );

links.last().on( "click", async ( event ) =>
{
	// On cesse d'abord le comportement par défaut.
	event.preventDefault();

	// On récupère après les nouvelles informations de connexion.
	const username = prompt( window.recover_password_username );
	const password = prompt( window.recover_password_password );

	if ( !username || !password )
	{
		return;
	}

	// On réalise ensuite la requête AJAX.
	const parent = $( event.target ).parent();
	const response = await fetch( parent.data( "route" ), {
		method: "PUT",
		headers: {
			"Content-Type": "application/x-www-form-urlencoded"
		},
		body: new URLSearchParams( {
			// Jeton de sécurité (CSRF).
			token: parent.data( "token" ),

			// Nom d'utilisateur associé au compte.
			username,

			// Nouveau mot de passe.
			password
		} )
	} );

	// On affiche enfin un message de confirmation ou d'erreur.
	addQueuedNotification( await response.text(), response.ok ? 3 : 1 );
} );

//
// Permet d'afficher en clair les mots de passe entrés dans les champs
//  de saisies dédiés dans les différents formulaire.
//
$( "[id *= clear]" ).on( "click", ( event ) =>
{
	// On recherche d'abord le champ de saisie des mots de passe.
	const input = $( event.target ).parent().find( "[id *= password]" );

	// On bascule enfin le type du champ entre « password » et « text ».
	input.attr( "type", ( input.attr( "type" ) === "password" ) ? "text" : "password" );
} );

//
// Permet de générer un mot de passe pseudo-sécurisé pour l'utilisateur.
//  Source : https://dev.to/code_mystery/random-password-generator-using-javascript-6a
//
const characters = "0123456789abcdefghijklmnopqrstuvwxyz!@#$%^&*()ABCDEFGHIJKLMNOPQRSTUVWXYZ";
let oldPassword = "";

$( "#register_generation" ).on( "click", ( event ) =>
{
	// On récupère le champ de saisie associé au formulaire.
	const target = $( event.target );
	const input = target.parent().find( "[id *= password]" );

	// On vérifie alors si la boite est cochée ou non.
	if ( target.is( ":checked" ) )
	{
		// Si elle est coché, on génère aléatoirement un mot de passe
		//  grâce à une série de caractères.
		let newPassword = "";

		for ( let indice = 0; indice <= 15; indice++ )
		{
			// On choisit un caractère aléatoirement dans la liste disponibles.
			const random = Math.floor( Math.random() * characters.length );

			// On l'ajoute ensuite dans le nouveau mot de passe généré.
			newPassword += characters.charAt( random );
		}

		// On enregistre enfin l'ancien mot de passe en mémoire avant de
		//  définir le mot de passe sécurisé dans le champ approprié.
		oldPassword = input.val() as string;

		input.val( newPassword );
	}
	else
	{
		// Dans le cas contraire, on restore l'ancien mot de passe.
		input.val( oldPassword );
	}
} );

//
// Permet de contrôler le mécanisme de présentation des fonctionnalités
//  principales du site.
//
const informations = $( "#informations" ); // Conteneur général des informations.
const elements = informations.find( "ul" ); // Les deux listes : paragraphes et images.
const images = elements.first().children(); // Liste des images.
const texts = elements.last().children(); // Liste des paragraphes.
const length = images.length - 1; // Longueur de la liste des éléments.

// Permet d'afficher les éléments initiaux de présentation.
function displayInitialElements()
{
	const article = informations.find( "article *" ); // Récupération des éléments de présentation.
	const indice = texts.filter( ":visible" ).index(); // Récupération de la position de l'élément visible.
	const show = indice === 0; // Détermination de son affichage ou non.

	// Apparition/disparition du logo et du lien cliquable.
	article.slice( 0, 2 ).css( "display", show ? "revert" : "none" );

	// Apparition/disparition du bouton pour revenir à un élément précédent.
	article.slice( 2, 4 ).css( "display", show ? "none" : "revert" );
}

// Permet d'exécuter le mécanisme de défilement précédent/suivant.
function updateInformation( forward: boolean )
{
	// Défilement des images.
	images.each( ( indice, image ) =>
	{
		// On vérifie si l'image est actuellement visible.
		const element = $( image );

		if ( element.is( ":visible" ) )
		{
			// Dans ce cas, on cache progressivement l'image actuelle.
			element.fadeOut( 200, () =>
			{
				// On vérifie ensuite si l'utilisateur demande d'avancer
				//  ou de reculer dans les positions des images.
				const nextIndex = forward ? indice + 1 : indice - 1;
				const nextImage = images.eq( nextIndex >= 0 ? nextIndex % length : length );
				nextImage.fadeIn( 150 );
			} );
		}
	} );

	// Défilement des paragraphes.
	texts.each( ( indice, text ) =>
	{
		// Vérification de la visibilité de l'élément.
		const element = $( text );

		if ( element.is( ":visible" ) )
		{
			element.fadeOut( 200, () =>
			{
				// Mécanisme de précédent/suivant.
				const nextIndex = forward ? indice + 1 : indice - 1;
				const nextText = texts.eq( nextIndex >= 0 ? nextIndex % length : length );
				nextText.fadeIn( 150 );

				// Mise à jour des éléments de présentation.
				displayInitialElements();
			} );
		}
	} );
}

informations.on( "click", "button:first-of-type", () =>
{
	// Bouton pour voir l'information précédente.
	updateInformation( false );
} );

informations.on( "click", "button:last-of-type", () =>
{
	// Bouton pour voir l'information suivante.
	updateInformation( true );
} );