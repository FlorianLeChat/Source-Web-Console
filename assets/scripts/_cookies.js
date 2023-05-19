//
// Permet de demander le consentement de l'utilisateur pour utiliser le mécanisme des cookies.
//  Note : ne s'applique pas à la page des mentions légales.
//  Source : https://github.com/orestbida/cookieconsent#all-configuration-options
//
import "vanilla-cookieconsent";

if ( window.location.search !== "?target=legal" )
{
	// On initialise le mécanisme de consentement.
	const cookie = initCookieConsent();

	// On force l'utilisation du thème sombre pour la fenêtre.
	$( "body" ).addClass( "c_darkmode" );

	// On exécute alors le mécanisme en suivant certaines consignes.
	cookie.run( {
		// On définit d'abord nos paramètres personnalisés.
		page_scripts: false, // Désactivation de la gestion des scripts
		force_consent: true, // Le consentement est obligatoire.
		auto_language: "document", // Langue sélectionnée par l'utilisateur.
		cookie_expiration: 31, // Temps d'expiration du cookie (en jours).

		onAccept: ( cookie ) =>
		{
			// Lors de chaque chargement de page, on itère à travers toutes les
			//  autorisations pour déterminer si les balises de signalement de
			//  Google Analytics doivent être utilisées.
			cookie.categories.forEach( ( level ) =>
			{
				if ( level === "analytics" )
				{
					sendAnalytics();
				}
			} );
		},

		// On définit enfin les traductions globales pour la bibliothèque.
		languages: {
			// Traductions anglaises.
			en: {
				consent_modal: {
					// Première partie de la fenêtre.
					title: "Do you want a cookie?",
					description: "Hello, this website uses essential cookies to ensure its proper functioning and tracking cookies to understand how you interact with it. The latter will only be set with your consent. <button type=\"button\" data-cc=\"c-settings\" class=\"cc-link\">Please let me choose</button>.",
					primary_btn: {
						// Bouton pour accepter tous les cookies.
						text: "Accept all",
						role: "accept_all"
					},
					secondary_btn: {
						// Boutons pour refuser tous les cookies.
						text: "Refuse all",
						role: "accept_necessary"
					}
				},
				settings_modal: {
					// Seconde partie de la fenêtre.
					title: "Cookie preferences",
					save_settings_btn: "Save settings",
					accept_all_btn: "Accept all",
					reject_all_btn: "Refuse all",
					close_btn_label: "Fermer",
					cookie_table_headers: [
						// Format de l'en-tête des cookies.
						{ col1: "Nom" },
						{ col2: "Domaine" },
						{ col3: "Description" }
					],
					blocks: [
						{
							// En-tête de la fenêtre.
							title: "Cookie Usage 📢",
							description: "I use cookies to provide basic website functionality and to enhance your online experience. For each category, you can choose to accept or decline cookies whenever you want. For more details about cookies and other sensitive data, please read the full <a href=\"?target=legal\" class=\"cc-link\">privacy policy</a>.",
						},
						{
							// Première option.
							title: "Strictly necessary cookies",
							description: "These cookies are essential to the proper functioning of the website. Without these cookies, the site would not function properly.",
							toggle: {
								value: "necessary",
								enabled: true,
								readonly: true
							}
						},
						{
							// Deuxième option.
							title: "Performance and analysis cookies",
							description: "These cookies allow the website to remember choices you have made in the past.",
							toggle: {
								value: "analytics",
								enabled: false,
								readonly: false
							},
							cookie_table: [
								{
									col1: "^_ga",
									col2: "analytics.google.com",
									col3: "Date and time the page was loaded.",
									is_regex: true
								},
								{
									col1: "_gid",
									col2: "analytics.google.com",
									col3: "Unique identifier associated with the current website."
								}
							]
						}
					]
				}
			},

			// Traductions françaises.
			fr: {
				consent_modal: {
					// Première partie de la fenêtre.
					title: "Vous voulez un cookie ?",
					description: "Bonjour, ce site Internet utilise des cookies essentiels pour assurer son bon fonctionnement et des cookies de suivi pour comprendre comment vous interagissez avec lui. Ces derniers ne seront mis en place qu'après consentement. <button type=\"button\" data-cc=\"c-settings\" class=\"cc-link\">Laissez moi choisir</button>.",
					primary_btn: {
						// Bouton pour accepter tous les cookies.
						text: "Tout accepter",
						role: "accept_all"
					},
					secondary_btn: {
						// Boutons pour refuser tous les cookies.
						text: "Tout refuser",
						role: "accept_necessary"
					}
				},
				settings_modal: {
					// Seconde partie de la fenêtre.
					title: "Préférences en matière de cookies",
					save_settings_btn: "Sauvegarder les paramètres",
					accept_all_btn: "Tout accepter",
					reject_all_btn: "Tout refuser",
					close_btn_label: "Fermer",
					cookie_table_headers: [
						// Format de l'en-tête des cookies.
						{ col1: "Nom" },
						{ col2: "Domaine" },
						{ col3: "Description" }
					],
					blocks: [
						{
							// En-tête de la fenêtre.
							title: "Utilisation des cookies 📢",
							description: "J'utilise des cookies pour assurer les fonctionnalités de base du site Internet et pour améliorer votre expérience en ligne. Pour chaque catégorie, vous pouvez choisir d'accepter ou de refuser les cookies quand vous le souhaitez. Pour plus de détails relatifs aux cookies et autres données sensibles, veuillez lire l'intégralité de la rubrique <a href=\"?target=legal\" class=\"cc-link\">politique de confidentialité</a>.",
						},
						{
							// Première option.
							title: "Cookies strictement nécessaires",
							description: "Ces cookies sont essentiels au bon fonctionnement du site Internet. Sans ces cookies, le site ne fonctionnerait pas correctement.",
							toggle: {
								value: "necessary",
								enabled: true,
								readonly: true
							}
						},
						{
							// Deuxième option.
							title: "Cookies de performance et d'analyse",
							description: "Ces cookies permettent au site Internet de se souvenir des choix que vous avez faits dans le passé.",
							toggle: {
								value: "analytics",
								enabled: false,
								readonly: false
							},
							cookie_table: [
								{
									col1: "^_ga",
									col2: "analytics.google.com",
									col3: "Date et heure de chargement de la page.",
									is_regex: true
								},
								{
									col1: "_gid",
									col2: "analytics.google.com",
									col3: "Identifiant unique associé au site Internet actuel."
								}
							]
						}
					]
				}
			}
		}
	} );
}