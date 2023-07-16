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
$( "#controller" ).on( "click", "button", ( event ) =>
{
	// On récupère le contenu de l'entrée utilisateur avant
	//  de le vérifie pour la prochaine étape.
	const target = $( event.target );
	const input = target.prev().val() as string;

	if ( !input )
	{
		// C'est une chaîne vide.
		return;
	}

	// On envoie ensuite le contenu au serveur distant.
	const parent = target.parent();
	sendRemoteAction( input, parent.data( "route" ), parent.data( "action" ) );

	// Une fois réalisée, on ajoute une entrée dans l'historique
	//  des entrées juste au-dessous.
	target.closest( "div" ).find( "ul" ).append( $( "<li></li>" ).text( input ) );

	// On réinitialise enfin le champ de saisie.
	target.prev().val( "" );
} );