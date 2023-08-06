//
// Permet de demander le consentement de l'utilisateur pour utiliser le mécanisme des cookies.
//  Note : ne s'applique pas à la page des mentions légales.
//  Source : https://cookieconsent.orestbida.com/reference/configuration-reference.html
//
import { run } from "vanilla-cookieconsent";
import { sendAnalytics, setupRecaptcha } from "./analytics";

if ( window.location.search !== "legal" )
{
	// On force l'utilisation du thème sombre pour la fenêtre.
	$( "html" ).addClass( "cc--darkmode" );

	// On lance le mécanisme de consentement des cookies.
	run(
		{
			// Activation automatique de la fenêtre de consentement.
			autoShow: process.env.NODE_ENV === "production",

			// Désactivation de l'interaction avec la page.
			disablePageInteraction: true,

			// Disparition du mécanisme pour les robots.
			hideFromBots: process.env.NODE_ENV === "production",

			// Paramètres internes des cookies.
			cookie: {
				name: "SYMFONY_ANALYTICS"
			},

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
				},
				security: {
					autoClear: {
						cookies: [
							{
								name: /^(OTZ|__Secure-ENID|SOCS|CONSENT|AEC)/
							}
						]
					}
				}
			},

			// Configuration des traductions.
			language: {
				default: "en",
				autoDetect: "document",
				translations: {
					en: "translations/en",
					fr: "translations/fr"
				}
			},

			// Exécution des actions de consentement.
			onConsent: ( { cookie } ) => (
				cookie.categories.forEach( ( category ) =>
				{
					switch ( category )
					{
						case "analytics":
							sendAnalytics();
							break;

						case "security":
							setupRecaptcha();
							break;

						default:
							break;
					}
				} )
			)
		}
	);
}