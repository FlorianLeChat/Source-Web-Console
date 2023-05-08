// Importation des feuilles de style.
import "../styles/desktop/index.scss";
import "../styles/phone/index.scss";
import "../styles/tablet/index.scss";

//
// Permet de gérer les ouvertures/fermetures de certains
//  formulaires de la page d'accueil.
//
const login = $( "#login" );
const header = $( "header li" );
const register = $( "#register article" );

header.first().find( "button" ).on( "click", () =>
{
	// Inscription (première partie).
	register.first().fadeIn( 150 );
} );

header.last().find( "button" ).on( "click", () =>
{
	// Connexion.
	login.fadeIn( 150 );
} );

//
// Permet de gérer les mécanismes du formulaire d'inscription.
//
const firstStep = register.first();
const lastStep = register.last();

register.find( "form" ).on( "submit", ( event ) =>
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
		// Dans le cas contraire, on réalise une requête AJAX pour
		//  envoyer les informations au serveur.
		$.post( "api/user/register", {

			// Nom d'utilisateur et mot de passe du compte utilisateur.
			username: firstStep.find( "input[name = username]" ).val(),
			password: firstStep.find( "input[name = password]" ).val(),

			// Informations du client (obligatoires côté serveur).
			server_address: lastStep.find( "input[name = server_address]" ).val(),
			server_port: lastStep.find( "input[name = server_port]" ).val(),

			// Informations administrateur (facultatives).
			admin_address: lastStep.find( "input[name = admin_address]" ).val(),
			admin_port: lastStep.find( "input[name = admin_port]" ).val(),
			admin_password: lastStep.find( "input[name = admin_password]" ).val(),

			// Options de connexion.
			//  Note : conversion explicite de la valeur booléenne en valeur entière.
			secure_only: lastStep.find( "input[id = secure_only]" ).is( ":checked" ) && 0,
			auto_connect: lastStep.find( "input[id = auto_connect]" ).is( ":checked" ) && 0

		} )
			.done( ( data ) =>
			{
				// Une fois terminée, on affiche la réponse JSON du
				//  serveur sous forme d'une liste numérique.
				const json = JSON.parse( data );

				// On affiche un message de confirmation.
				addQueuedNotification( json[ 0 ], json[ 1 ] );

				// On effectue par la suite certaines actions si le message
				//  renvoyé par le serveur est un message de succès.
				if ( json[ 1 ] === 2 )
				{
					// On réinitialise alors les deux formulaires avant
					//  de fermer le second.
					firstStep.find( "form" )[ 0 ].reset();
					lastStep.find( "form" )[ 0 ].reset();

					lastStep.fadeOut( 150 );

					// On effectue enfin la redirection de l'utilisateur
					//  vers le tableau de bord au bout de 5 secondes.
					setTimeout( () =>
					{
						window.location.href = "?target=dashboard";
					}, 5000 );
				}
			} )
			.fail( ( self, _status, error ) =>
			{
				// Dans le cas contraire, on affiche une notification
				//  d'échec avec les informations à notre disposition.
				addQueuedNotification( form_register_failed.replace( "$1", getStatusText( error, self.status ) ), 1 );
			} );
	}
} );

register.find( "input[type = reset]" ).on( "click", () =>
{
	// On vérifie d'abord si l'utilisateur se trouve ou non
	//  à la première étape de l'inscription.
	if ( firstStep.is( ":visible" ) )
	{
		// Si c'est le cas, on cache le formulaire..
		firstStep.fadeOut( 150 );

		// ..avant de réinitialiser les informations des deux parties.
		firstStep.find( "form" )[ 0 ].reset();
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
login.find( "input[type = submit]" ).on( "click", ( event ) =>
{
	// On cesse d'abord le comportement par défaut.
	event.preventDefault();

	// On réalise ensuite la requête AJAX.
	$.post( "api/user/login", {

		// Nom d'utilisateur.
		username: login.find( "input[name = user_name]" ).val(),

		// Mot de passe.
		password: login.find( "input[name = user_password]" ).val(),

		// Option de maintien de connexion.
		remember_me: login.find( "input[id = remember_me]" ).is( ":checked" ) && 0

	} )
		.done( ( data ) =>
		{
			// Une fois terminée, on affiche la réponse JSON du
			//  serveur sous forme d'une liste numérique.
			const json = JSON.parse( data );

			// On affiche alors un message de confirmation.
			addQueuedNotification( json[ 0 ], json[ 1 ] );

			// On effectue par la suite certaines actions si le message
			//  renvoyé par le serveur est un message de succès.
			if ( json[ 1 ] === 2 )
			{
				// On réinitialise alors l'entièreté du formulaire.
				login.find( "form" )[ 0 ].reset();
				login.fadeOut( 150 );

				// On effectue enfin la redirection de l'utilisateur
				//  vers le tableau de bord au bout de 5 secondes.
				setTimeout( () =>
				{
					window.location.href = "?target=dashboard";
				}, 3000 );
			}
		} )
		.fail( ( self, _status, error ) =>
		{
			// Dans le cas contraire, on affiche une notification
			//  d'échec avec les informations à notre disposition.
			addQueuedNotification( form_login_failed.replace( "$1", getStatusText( error, self.status ) ), 1 );
		} );
} );

login.find( "input[type = reset]" ).on( "click", () =>
{
	// On cache le formulaire à la demande de l'utilisateur.
	login.hide();
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
		register.first().fadeIn( 150 );
	} );
} );

links.last().on( "click", ( event ) =>
{
	// On cesse d'abord le comportement par défaut.
	event.preventDefault();

	// On réalise ensuite la requête AJAX.
	$.post( "api/user/login", {

		// Nom d'utilisateur.
		username: prompt( recover_password_username ),

		// Mot de passe.
		password: prompt( recover_password_password ),

		// Option de récupération.
		backup: true

	} )
		.done( ( data ) =>
		{
			// Une fois terminée, on affiche la réponse JSON du
			//  serveur sous forme d'une liste numérique.
			const json = JSON.parse( data );

			// On affiche enfin le message de confirmation.
			addQueuedNotification( json[ 0 ], json[ 1 ] );
		} )
		.fail( ( self, _status, error ) =>
		{
			// Dans le cas contraire, on affiche une notification
			//  d'échec avec les informations à notre disposition.
			addQueuedNotification( form_login_failed.replace( "$1", getStatusText( error, self.status ) ), 1 );
		} );
} );

//
// Permet d'afficher en clair les mots de passe entrés dans les champs
//  de saisies dédiés dans les différents formulaire.
//
$( "input[id *= clear]" ).on( "click", ( event ) =>
{
	// On recherche le champ de saisie des mots de passe.
	const input = $( event.target ).parent().find( "input[id *= password]" );

	// On vérifie ensuite son état actuel.
	if ( input.attr( "type" ) === "password" )
	{
		// Alors on définit le type du champ en texte pour afficher
		//  le contenu en clair sans les pointillés habituels.
		input.attr( "type", "text" );
	}
	else
	{
		// Dans le cas contraire, on remet son état initial.
		input.attr( "type", "password" );
	}
} );

//
// Permet de générer un mot de passe pseudo-sécurisé pour l'utilisateur.
//  Source : https://dev.to/code_mystery/random-password-generator-using-javascript-6a
//
const characters = "0123456789abcdefghijklmnopqrstuvwxyz!@#$%^&*()ABCDEFGHIJKLMNOPQRSTUVWXYZ";
let oldPassword = "";

$( "#generation" ).on( "click", ( event ) =>
{
	// On récupère le champ de saisie associé au formulaire.
	const input = $( event.target ).parent().find( "input[id *= password]" );

	// On vérifie alors si la boite est cochée ou non.
	if ( $( event.target ).is( ":checked" ) )
	{
		// Si elle est coché, on génère aléatoirement un mot de passe
		//  grâce à une série de caractères.
		let newPassword = "";

		for ( let indice = 0; indice <= 15; indice++ )
		{
			// On choisit un caractère aléatoirement dans la liste disponibles.
			const random = Math.floor( Math.random() * characters.length );

			// On l'ajoute ensuite dans le nouveau mot de passe généré.
			newPassword += characters.substring( random, random + 1 );
		}

		// On enregistre enfin l'ancien mot de passe en mémoire avant de
		//  définir le mot de passe sécurisé dans le champ approprié.
		oldPassword = input.val();

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
function updateInformation( forward )
{
	// Défilement des images.
	images.ForEach( ( image, indice ) =>
	{
		// On vérifie si l'image est actuellement visible.
		const element = $( image );

		if ( element.is( ":visible" ) )
		{
			// Dans ce cas, on cache progressivement l'image actuelle.
			element.fadeOut( 200, () =>
			{
				// On vérifie ensuite si l'utilisateur demander d'avancer
				//  ou de reculer dans les positions des images.
				if ( forward )
				{
					// Pour avancer, on vérifie si on atteint pas le dépassement
					//  du nombre d'images disponibles.
					if ( indice >= length )
					{
						// Dans ce cas, on affiche la première image de la liste.
						images.first().fadeIn( 150 );
					}
					else
					{
						// Dans le cas contraire, on affiche la suivante.
						element.next().fadeIn( 150 );
					}
				}
				// En cas de reculement, on vérifie la position actuelle
				//  dans la liste.
				else if ( indice === 0 )
				{
					// Si on atteint le début de la liste, on affiche la dernière
					//  image disponible.
					images.last().fadeIn( 150 );
				}
				else
				{
					// Dans le cas contraire, on affiche la précédente.
					element.prev().fadeIn( 150 );
				}
			} );
		}
	} );

	// Défilement des paragraphes.
	texts.ForEach( ( text, indice ) =>
	{
		// Vérification de la visibilité de l'élément.
		const element = $( text );

		if ( element.is( ":visible" ) )
		{
			element.fadeOut( 200, () =>
			{
				// Mécanisme de précédent/suivant.
				if ( forward )
				{
					// Texte suivant.
					if ( indice >= length )
					{
						texts.first().fadeIn( 150 );
					}
					else
					{
						element.next().fadeIn( 150 );
					}
				}
				// Texte précédent.
				else if ( indice === 0 )
				{
					texts.last().fadeIn( 150 );
				}
				else
				{
					element.prev().fadeIn( 150 );
				}

				// Mise à jour des éléments de présentation.
				displayInitialElements();
			} );
		}
	} );
}

informations.find( "button" ).first().on( "click", () =>
{
	// Bouton pour voir l'information précédente.
	updateInformation( false );
} );

informations.find( "button" ).last().on( "click", () =>
{
	// Bouton pour voir l'information suivante.
	updateInformation( true );
} );