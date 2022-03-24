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
	signup.first().show();
} );

header.last().find( "button" ).click( function ()
{
	// Connexion.
	signin.show();
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
		// S'il est à la première étape, alors on réalise des vérifications.
		if ( false )
		{
			return;
		}

		event.preventDefault();

		// Une fois terminé, on cache la première étape pour passer à la suivante.
		first_step.hide();
		last_step.show();
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
	first_step.hide();
	last_step.hide();

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
	signin.hide();
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
	signin.hide();
	signup.first().show();
} );

links.last().click( function ()
{
	// Redirection vers la connexion unique.
	signin.hide();
	signup.last().show();
} );