// Importation des feuilles de style.
import "../../styles/desktop/console.scss";
import "../../styles/phone/console.scss";
import "../../styles/tablet/console.scss";

// Importation des fonctions et constantes communes.
import "../global";
import { sendRemoteAction } from "../functions";

//
// Permet d'envoyer les entrées utilisateurs personnalisées
//  au serveur distant.
//
$( "#controller button" ).on( "click", ( event ) =>
{
	// On récupère le contenu de l'entrée utilisateur avant
	//  de le vérifie pour la prochaine étape.
	const target = $( event.target );
	const input = target.prev().val();

	if ( input.trim() === "" || input.length === 0 )
	{
		// C'est une chaîne vide.
		return;
	}

	// On envoie ensuite le contenu au serveur distant.
	const parent = target.parent();
	sendRemoteAction( input, parent.attr( "data-route" ), parent.attr( "data-action" ) );

	// Une fois réalisée, on ajoute une entrée dans l'historique
	//  des entrées juste au-dessous.
	$( event.target ).parent().parent().find( "ul" )
		.append( $( "<li></li>" ).text( input ) );

	// On réinitialise enfin le champ de saisie.
	$( event.target ).prev().val( "" );
} );