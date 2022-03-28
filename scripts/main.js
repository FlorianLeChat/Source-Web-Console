//
// Permet de gérer les mécanismes du formulaire de contact.
//
const contact = $( "#contact" );

contact.find( "input[type = submit]" ).click( function ( event )
{
	if ( true )
	{
		// On réalise les vérifications avant de soumettre le formulaire.
		event.preventDefault();
	}
} );

contact.find( "input[type = reset]" ).click( function ()
{
	// On cache le formulaire à la demande de l'utilisateur.
	contact.hide();
} );

//
// Permet d'ouvrir le formulaire de contact via le pied de page.
//
$( "footer" ).find( "a[href = \"javascript:void(0);\"]" ).click( function ()
{
	contact.show();
} );


//
// Permet de désactiver le mécanisme de glissement des liens.
//
$( "a" ).mousedown( function ( event )
{
	event.preventDefault();
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
// Permet de bloquer le renvoie des formulaires lors du rafraîchissement
//	de la page par l'utilisateur.
// 	Source : https://stackoverflow.com/a/45656609
//
if ( window.history.replaceState && window.location.hostname != "localhost" )
{
	window.history.replaceState( null, null, window.location.href );
}