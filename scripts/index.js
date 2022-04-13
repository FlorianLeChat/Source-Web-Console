//
// Permet de gérer les ouvertures/fermetures de certains
//	formulaires de la page d'accueil.
//
const header = $( "header li" );
const signup = $( "#signup article" );
const signin = $( "#signin" );

header.first().find( "button" ).click( function ()
{
	// Inscription (première partie).
	signup.first().fadeIn( 150 );
} );

header.last().find( "button" ).click( function ()
{
	// Connexion.
	signin.fadeIn( 150 );
} );

//
// Permet de gérer les mécanismes du formulaire d'inscription.
//
const first_step = signup.first();
const last_step = signup.last();

signup.find( "form" ).submit( function ( event )
{
	// On cesse d'abord le comportement par défaut.
	event.preventDefault();

	// On vérifie ensuite si l'utilisateur se trouve à la première
	//	ou à la deuxième étape de la phase d'inscription.
	if ( first_step.is( ":visible" ) )
	{
		// Si c'est le cas, on passe à la seconde étape.
		first_step.fadeOut( 150, function ()
		{
			last_step.fadeIn( 150 );
		} );
	}
	else
	{
		// Dans le cas contraire, on réalise une requête AJAX pour
		//	envoyer les informations au serveur.
		$.post( "includes/controllers/signup.php", {

			// Nom d'utilisateur et mot de passe du compte utilisateur.
			username: first_step.find( "input[name = username]" ).val(),
			password: first_step.find( "input[name = password]" ).val(),

			// Informations du client (obligatoires côté serveur).
			server_address: last_step.find( "input[name = server_address]" ).val(),
			server_port: last_step.find( "input[name = server_port]" ).val(),

			// Informations administrateur (facultatives).
			admin_address: last_step.find( "input[name = admin_address]" ).val(),
			admin_port: last_step.find( "input[name = admin_port]" ).val(),
			admin_password: last_step.find( "input[name = admin_password]" ).val(),

			// Options de connexion.
			//	Note : conversion explicite de la valeur booléenne en valeur entière.
			secure_only: last_step.find( "input[id = secure_only]" ).is( ":checked" ) | 0,
			auto_connect: last_step.find( "input[id = auto_connect]" ).is( ":checked" ) | 0

		} )
			.done( function ( data, _status, _self )
			{
				// Une fois terminée, on affiche la réponse JSON du
				//	serveur sous forme d'une liste numérique.
				const json = JSON.parse( data );

				// On affiche un message de confirmation.
				addQueuedNotification( json[ 0 ], json[ 1 ] );

				// On effectue par la suite certaines actions si le message
				//	renvoyé par le serveur est un message de succès.
				if ( json[ 1 ] == 2 )
				{
					// On réinitialise alors les deux formulaires avant
					//	de fermer le second.
					first_step.find( "form" )[ 0 ].reset();
					last_step.find( "form" )[ 0 ].reset();

					last_step.fadeOut( 150 );

					// On effectue enfin la redirection de l'utilisateur
					//	vers le tableau de bord au bout de 5 secondes.
					setTimeout( function ()
					{
						window.location.href = "?target=dashboard";
					}, 5000 );
				}
			} )
			.fail( function ( _self, _status, error )
			{
				// Dans le cas contraire, on affiche une notification
				//	d'échec avec les informations à notre disposition.
				addQueuedNotification( form_signup_failed.replace( "$1", error ), 1 );
			} );
	}
} );

signup.find( "input[type = reset]" ).click( function ()
{
	// On vérifie d'abord si l'utilisateur se trouve ou non
	//	à la première étape de l'inscription.
	if ( first_step.is( ":visible" ) )
	{
		// Si c'est le cas, on cache le formulaire..
		first_step.fadeOut( 150 );

		// ..avant de réinitialiser les informations des deux parties.
		first_step.find( "form" )[ 0 ].reset();
		last_step.find( "form" )[ 0 ].reset();
	}
	else
	{
		// Dans le cas contraire, on retourne juste en arrière
		//	si l'utilisateur veut modifier certaines informations.
		first_step.fadeIn( 150 );
		last_step.fadeOut( 150 );
	}
} );

//
// Permet de gérer les mécanismes du formulaire de connexion.
//
signin.find( "input[type = submit]" ).click( function ( event )
{
	// On cesse d'abord le comportement par défaut.
	event.preventDefault();

	// On réalise ensuite la requête AJAX.
	$.post( "includes/controllers/signin.php", {

		// Nom d'utilisateur.
		username: signin.find( "input[name = user_name]" ).val(),

		// Mot de passe.
		password: signin.find( "input[name = user_password]" ).val(),

		// Option de maintien de connexion.
		remember_me: signin.find( "input[id = remember_me]" ).is( ":checked" ) | 0,

	} )
		.done( function ( data, _status, _self )
		{
			// Une fois terminée, on affiche la réponse JSON du
			//	serveur sous forme d'une liste numérique.
			const json = JSON.parse( data );

			// On affiche alors un message de confirmation.
			addQueuedNotification( json[ 0 ], json[ 1 ] );

			// On effectue par la suite certaines actions si le message
			//	renvoyé par le serveur est un message de succès.
			if ( json[ 1 ] == 2 )
			{
				// On réinitialise alors l'entièreté du formulaire.
				signin.find( "form" )[ 0 ].reset();
				signin.fadeOut( 150 );

				// On effectue enfin la redirection de l'utilisateur
				//	vers le tableau de bord au bout de 5 secondes.
				setTimeout( function ()
				{
					window.location.href = "?target=dashboard";
				}, 5000 );
			}
		} )
		.fail( function ( _self, _status, error )
		{
			// Dans le cas contraire, on affiche une notification
			//	d'échec avec les informations à notre disposition.
			addQueuedNotification( form_signin_failed.replace( "$1", error ), 1 );
		} );
} );

signin.find( "input[type = reset]" ).click( function ()
{
	// On cache le formulaire à la demande de l'utilisateur.
	signin.hide();
} );

//
// Permet de gérer les liens de redirection présents dans le
//	formulaire de connexion.
//
const links = signin.find( "a[href = \"javascript:void(0);\"]" );

links.first().click( function ()
{
	signin.fadeOut( 150, function ()
	{
		// Redirection vers l'inscription.
		signup.first().fadeIn( 150 );
	} );
} );

links.eq( 1 ).click( function ()
{
	signin.fadeOut( 150, function ()
	{
		// Redirection vers la connexion unique.
		signup.last().fadeIn( 150 );
	} );
} );

links.last().click( function ()
{
	// Mot de passe oublié.
	alert( "Implémentation nécessaire." );
} );

//
// Permet d'afficher en clair les mots de passe entrés dans les champs
//	de saisies dédiés dans les différents formulaire.
//
$( "input[id *= clear]" ).click( function ()
{
	// On recherche le champ de saisie des mots de passe.
	const input = $( this ).parent().find( "input[id *= password]" );

	// On vérifie ensuite son état actuel.
	if ( input.attr( "type" ) == "password" )
	{
		// Alors on définit le type du champ en texte pour afficher
		//	le contenu en clair sans les pointillés habituels.
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
// 	Source : https://dev.to/code_mystery/random-password-generator-using-javascript-6a
//
const characters = "0123456789abcdefghijklmnopqrstuvwxyz!@#$%^&*()ABCDEFGHIJKLMNOPQRSTUVWXYZ";
let old_password = "";

$( "#generation" ).click( function ()
{
	// On récupère le champ de saisie associé au formulaire.
	const input = $( this ).parent().find( "input[id *= password]" );

	// On vérifie alors si la boite est cochée ou non.
	if ( $( this ).is( ":checked" ) )
	{
		// Si elle est coché, on génère aléatoirement un mot de passe
		//	grâce à une série de caractèrs.
		let new_password = "";

		for ( let indice = 0; indice <= 15; indice++ )
		{
			// On choisit un caractère aléatoirement dans la liste disponibles.
			const random = Math.floor( Math.random() * characters.length );

			// On l'ajoute ensuite dans le nouveau mot de passe généré.
			new_password += characters.substring( random, random + 1 );
		}

		// On enregistre enfin l'ancien mot de passe en mémoire avant de
		//	définir le mot de passe sécurisé dans le champ approprié.
		old_password = input.val();

		input.val( new_password );
	}
	else
	{
		// Dans le cas contraire, on restore l'ancien mot de passe.
		input.val( old_password );
	}
} )

//
// Permet de contrôler le mécanisme de présentation des fonctionnalités
//	principales du site.
//
const informations = $( "#informations" );		// Conteneur général des informations.
const elements = informations.find( "ul" ); 	// Les deux listes : paragraphes et images.
const images = elements.first().children(); 	// Liste des images.
const texts = elements.last().children();		// Liste des paragraphes.
const length = images.length - 1;				// Longueur de la liste des éléments.

// Permet d'afficher les éléments initiaux de présentation.
function displayInitialElements()
{
	const header = informations.find( "article *" );	// Récupération des éléments de présentation.
	const indice = texts.filter( ":visible" ).index();	// Récupération de la position de l'élément visible.
	const show = indice == 0 ? true : false;			// Détermination de son affichage ou non.

	// Apparition/disparition des éléments de présentation.
	header.slice( 0, 2 ).css( "display", show ? "revert" : "none" );

	// Apparition/disparition du bouton pour revenir à un élément précédent.
	header.eq( 2 ).css( "display", show ? "none" : "revert" );
}

// Permet d'exécuter le mécanisme de défilement précédent/suivant.
function updateInformation( forward )
{
	// Défilement des images.
	for ( const indice in images )
	{
		const element = $( images[ indice ] );

		// On vérifie si l'image est actuellement visible.
		if ( element.is( ":visible" ) )
		{
			// Dans ce cas, on cache progressement l'image actuelle.
			element.fadeOut( 200, function ()
			{
				// On vérifie ensuite si l'utilisateur demander d'avancer
				//	ou de reculer dans les positions des images.
				if ( forward )
				{
					// Pour avancer, on vérifie si on atteint pas le dépassement
					//	du nombre d'images disponibles.
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
				else
				{
					// En cas de reculement, on vérifie la position actuelle
					//	dans la liste.
					if ( indice == 0 )
					{
						// Si on atteint le début de la liste, on affiche la dernière
						//	image disponible.
						images.last().fadeIn( 150 );
					}
					else
					{
						// Dans le cas contraire, on affiche la précédente.
						element.prev().fadeIn( 150 );
					}
				}
			} );

			break;
		}
	}

	// Défilement des paragraphes.
	for ( const indice in texts )
	{
		const element = $( texts[ indice ] );

		// Vérification de la visilité de l'élement.
		if ( element.is( ":visible" ) )
		{
			element.fadeOut( 200, function ()
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
				else
				{
					// Texte précédent.
					if ( indice == 0 )
					{
						texts.last().fadeIn( 150 );
					}
					else
					{
						element.prev().fadeIn( 150 );
					}
				}

				// Mise à jour des éléments de présentation.
				displayInitialElements();
			} );

			break;
		}
	}
}

informations.find( "button" ).first().click( function ()
{
	// Bouton pour voir l'information précédente.
	updateInformation( false );
} );

informations.find( "button" ).last().click( function ()
{
	// Bouton pour voir l'information suivante.
	updateInformation( true );
} );