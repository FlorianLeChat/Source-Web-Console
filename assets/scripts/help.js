// Importation des feuilles de style.
import "../styles/desktop/help.scss";
import "../styles/phone/help.scss";
import "../styles/tablet/help.scss";

//
// Permet de contrôler le mécanisme de basculement de la section
//	des questions/réponses de la page.
//	Source : https://www.w3schools.com/howto/howto_js_collapsible.asp
//
$( "#faq button" ).click( ( event ) =>
{
	// On bascule d'abord l'état d'activation du bouton.
	$( event.target ).toggleClass( "active" );

	// On récupère alors l'élément suivant le bouton.
	//	Note : on doit récupérer ici un élément <p>.
	const content = $( event.target ).next();

	// On vérifie ensuite si une taille maximale a été définie.
	if ( content.css( "maxHeight" ) != "0px" )
	{
		// Si c'est le cas, on supprime nos règles personnalisées.
		content.removeAttr( "style" );
	}
	else
	{
		// Dans le cas contraire, on définit plusieurs règles pour
		//	permettre l'apparition du paragraphe.
		content.css( "maxHeight", content.prop( "scrollHeight" ) + "px" );
		content.css( "paddingTop", "0.5rem" );
		content.css( "paddingBottom", "0.5rem" );
	}
} );