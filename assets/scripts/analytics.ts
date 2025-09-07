//
// Permet d'ajouter le mécanisme de fonctionnement de Google reCAPTCHA.
//  Source : https://www.google.com/recaptcha/about/
//
const head = $( "head" );

export function setupRecaptcha()
{
	// On vérifie d'abord si le service est activé ou non.
	if ( process.env.RECAPTCHA_ENABLED === "false" )
	{
		return;
	}

	// On installe ensuite le script de Google reCAPTCHA.
	const url = `https://www.google.com/recaptcha/api.js?render=${ window.recaptcha_public_key }`;
	head.append( `<script src="${ url }" async></script>` );

	// On envoie enfin une requête de vérification afin de mesurer
	//  le trafic du site par la console de Google reCAPTCHA.
	const timer = setInterval( () =>
	{
		if ( typeof window.grecaptcha !== "undefined" )
		{
			window.proxyFetch( "", { method: "POST" } );
			clearInterval( timer );
		}
	}, 1000 );
}