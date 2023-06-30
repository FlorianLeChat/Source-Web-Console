//
// Permet d'ajouter le mécanisme de fonctionnement de Google Analytics.
//  Source : https://analytics.google.com/analytics/web/#/
//
export default function sendAnalytics()
{
	window.dataLayer = window.dataLayer || [];

	function gtag( ...args )
	{
		window.dataLayer.push( ...args );
	}

	gtag( "js", new Date() );
	gtag( "config", window.analytics_identifier );
}