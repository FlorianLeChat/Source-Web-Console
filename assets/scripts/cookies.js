//
// Permet de demander le consentement de l'utilisateur pour utiliser le mécanisme des cookies.
//  Note : ne s'applique pas à la page des mentions légales.
//  Source : https://cookieconsent.orestbida.com/reference/configuration-reference.html
//
import { run } from "vanilla-cookieconsent";
import sendAnalytics from "./analytics";

if ( window.location.search !== "legal" )
{
	// On force l'utilisation du thème sombre pour la fenêtre.
	$( "body" ).addClass( "c_darkmode" );

	// On lance le mécanisme de consentement des cookies.
	run(
		{
			// Activation automatique de la fenêtre de consentement.
			autoShow: true,

			// Désactivation de l'interaction avec la page.
			disablePageInteraction: true,

			// Disparition du mécanisme pour les robots.
			hideFromBots: true,

			// Paramètres de l'interface utilisateur.
			guiOptions: {
				consentModal: {
					position: "bottom right"
				}
			},

			// Configuration des catégories de cookies.
			categories: {
				necessary: {
					enabled: true,
					readOnly: true
				},
				analytics: {
					autoClear: {
						cookies: [
							{
								name: /^(_ga|_gid)/
							}
						]
					}
				}
			},

			// Configuration des traductions.
			language: {
				default: "en",
				autoDetect: "browser",
				translations: {
					en: "locales/en.json",
					fr: "locales/fr.json"
				}
			},

			// Exécution des actions de consentement.
			onConsent: ( { cookie } ) => (
				cookie.categories.find( ( category ) => category === "analytics" ) && sendAnalytics()
			),

			// Exécution des actions de changement.
			onChange: ( { cookie } ) => (
				cookie.categories.find( ( category ) => category === "analytics" ) && sendAnalytics()
			)
		}
	);
}