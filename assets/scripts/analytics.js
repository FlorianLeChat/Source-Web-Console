//
// Permet d'ajouter le mécanisme de fonctionnement de Google Analytics.
//  Source : https://analytics.google.com/analytics/web/
//
export function sendAnalytics()
{
	// On ajoute d'abord le script de Google Analytics.
	const url = `https://www.googletagmanager.com/gtag/js?id=${ window.analytics_identifier }`;
	$( "head" ).append( `<script src="${ url }" async></script>` );

	// On ajoute enfin le script de configuration de Google Analytics.
	window.dataLayer = window.dataLayer || [];

	function gtag( ...args )
	{
		window.dataLayer.push( ...args );
	}

	gtag( "js", new Date() );
	gtag( "config", window.analytics_identifier );
}

//
// Permet d'ajouter le mécanisme de fonctionnement de Google reCAPTCHA.
//  Source : https://www.google.com/recaptcha/about/
//
export function setupRecaptcha()
{
	// On installe le script de Google reCAPTCHA.
	const url = `https://www.google.com/recaptcha/api.js?render=${ window.captcha_public_key }`;
	$( "head" ).append( `<script src="${ url }" async></script>` );
}