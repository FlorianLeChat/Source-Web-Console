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

contact.find( "form" ).submit( function ()
{
	// On réalise d'abord la requête AJAX.
	alert( "Réalisation de la requête AJAX." );

	// On réinitialise par la suite l'entièreté du formulaire.
	$( this ).reset();

	// On affiche enfin une notification de confirmation.
	alert( "Affichage d'une notification de confirmation." );
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
// Permet de bloquer le renvoie des formulaires lors du rafraîchissement
//	de la page par l'utilisateur.
// 	Source : https://stackoverflow.com/a/45656609
//
if ( window.history.replaceState && window.location.hostname != "localhost" )
{
	window.history.replaceState( null, null, window.location.href );
}