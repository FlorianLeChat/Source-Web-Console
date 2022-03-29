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

signup.find( "input[type = submit]" ).click( function ( event )
{
	// On vérifie si l'utilisateur se trouve à la première ou à la
	//	dernière étape de la phase d'inscription.
	if ( first_step.is( ":visible" ) )
	{
		// À la première étape, on casse le mécanisme de soumission
		//	et on passe au suivant.
		first_step.fadeOut( 150, function ()
		{
			last_step.fadeIn( 150 );
		} );

		event.preventDefault();
	}
	else
	{
		// Dans le cas de la deuxième étape, on réalise les vérifications
		//	avant de soumettre le formulaire au serveur.
		if ( true )
		{
			event.preventDefault();
		}
	}
} );

signup.find( "input[type = reset]" ).click( function ()
{
	// On cache le formulaire si l'utilisateur a demandé l'annulation de l'inscription.
	first_step.fadeOut( 150 );
	last_step.fadeOut( 150 );

	// On en profte également pour réinitialiser les valeurs de tout le formulaire.
	first_step.find( "form" )[ 0 ].reset();
	last_step.find( "form" )[ 0 ].reset();
} );

//
// Permet de gérer les mécanismes du formulaire de connexion.
//
signin.find( "input[type = submit]" ).click( function ( event )
{
	if ( true )
	{
		// On réalise les vérifications avant de soumettre le formulaire.
		event.preventDefault();
	}
} );

signin.find( "input[type = reset]" ).click( function ()
{
	// On cache le formulaire à la demande de l'utilisateur.
	signin.fadeOut( 150 );
} );

//
// Permet de gérer les liens de redirection présents dans le
//	formulaire de connexion.
//
const links = signin.find( "a[href = \"javascript:void(0);\"]" );

links.first().click( function ()
{
	// Mot de passe oublié.
	alert( "Implémentation nécessaire." );
} );

links.eq( 1 ).click( function ()
{
	// Redirection vers l'inscription.
	signin.fadeOut( 150, function ()
	{
		signup.first().fadeIn( 150 );
	} );
} );

links.last().click( function ()
{
	// Redirection vers la connexion unique.
	signin.fadeOut( 150, function ()
	{
		signup.last().fadeIn( 150 );
	} );
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