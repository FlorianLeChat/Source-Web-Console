//
// Permet d'ajouter le mécanisme de fonctionnement de Google Analytics.
//  Source : https://analytics.google.com/analytics/web/#/
//
export default function sendAnalytics()
{
	window.dataLayer = window.dataLayer || [];

	function gtag()
	{
		dataLayer.push( arguments );
	}

	gtag( "js", new Date() );
	gtag( "config", analytics_identifier );
}