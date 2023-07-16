//
// Permet d'ajouter le mécanisme de fonctionnement de Google Analytics.
//  Source : https://analytics.google.com/analytics/web/
//
const head = $( "head" );

export function sendAnalytics()
{
	// On ajoute d'abord le script de Google Analytics.
	const url = `https://www.googletagmanager.com/gtag/js?id=${ window.analytics_identifier }`;
	head.append( `<script src="${ url }" async></script>` );

	// On ajoute enfin le script de configuration de Google Analytics.
	window.dataLayer = window.dataLayer || [];

	function gtag( ...args: ( string | Date )[] )
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
	// On installe d'abord le script de Google reCAPTCHA.
	const url = `https://www.google.com/recaptcha/api.js?render=${ window.recaptcha_public_key }`;
	head.append( `<script src="${ url }" async></script>` );

	// On envoie enfin une requête de vérification afin de mesurer
	//  le trafic du site par la console de Google reCAPTCHA.
	const timer = setInterval( () =>
	{
		if ( typeof window.grecaptcha !== "undefined" )
		{
			fetch( "", { method: "POST" } );
			clearInterval( timer );
		}
	}, 1000 );
}