//
// Permet d'appliquer les images en arrière-plan des serveurs
//	en fonction de leur jeu installé.
//
const servers = $( "li[data-image]" );
let image_indice = 1;

for ( const server of servers )
{
	$( `<style>#servers li:nth-of-type(${ image_indice }):before { background-image: url(${ $( server ).attr( "data-image" ) })</style>` ).appendTo( "head" );

	image_indice++;
}

//
// Permet d'effectuer des actions sur les instances présentes
//	sur le tableau de bord.
//	Note : ce système peut largement être améliorable dans le futur.
//
let submit_edit = false;

$( "[name = server_edit]" ).click( function ( event )
{
	// On cesse d'abord le comportement par défaut.
	if ( submit_edit )
		return;

	// On cesse le comportement par défaut.
	event.preventDefault();

	// On récupère le parent de l'élément.
	const parent = $( this ).parent();

	// On demande ensuite à l'utilisateur s'il veut supprimer ou non
	//	l'instance.
	if ( confirm( "Voulez-vous supprimer ce serveur ?" ) )
	{
		// Suppression de l'action par défaut.
		$( "input[value = edit]" ).remove();

		// Ajout de l'action de suppression.
		parent.append( "<input type=\"hidden\" name=\"action\" value=\"delete\" />" );
	}
	else
	{
		// Adresse IP et port de communication du serveur.
		const client_address = prompt( "Saisissez l'adresse IP du serveur.\nLaissez vide pour aucun changement." );
		const client_port = prompt( "Saisissez le port de communication du serveur.\nLaissez vide pour aucun changement." );

		parent.append( `<input type=\"hidden\" name=\"client_address\" value=\"${ client_address }\" />` );
		parent.append( `<input type=\"hidden\" name=\"client_port\" value=\"${ client_port }\" />` );

		// Adresse IP, port et mot de passe administrateur.
		const admin_address = prompt( "Saisissez l'adresse IP administrateur.\nLaissez vide pour aucun changement." );
		const admin_port = prompt( "Saisissez le port de communication administrateur.\nLaissez vide pour aucun changement." );
		const admin_password = prompt( "Saisissez le mot de passe administrateur.\nLaissez vide pour aucun changement." );

		parent.append( `<input type=\"hidden\" name=\"admin_address\" value=\"${ admin_address }\" />` );
		parent.append( `<input type=\"hidden\" name=\"admin_port\" value=\"${ admin_port }\" />` );
		parent.append( `<input type=\"hidden\" name=\"admin_password\" value=\"${ admin_password }\" />` );
	}

	// On force enfin la soumission du formulaire en indiquant
	//	qu'on ne doit pas demander de nouveau les informations.
	submit_edit = true;

	$( this ).click();
} );

//
//
//
function retrieveRemoteData()
{
	//
	$.post( "includes/controllers/server_overview.php", {

		//
		server_address: current_address,

		//
		server_port: current_port,

		//
		server_password: current_password

	} )
		.done( function ( data, _status, _self )
		{
			console.log( data );
			//
			const json = JSON.parse( data );

			// break timer on error et indiquer rafraîchir page

			//
			$( "[data-field = state]" ).html( `En fonctionnement<br />(${ json[ "gamemode" ] })` );

			//
			$( "[data-field = map]" ).html( json[ "maps" ] );

			//
			$( "[data-field = players]" ).html( `${ json[ "players" ] } / ${ json[ "max_players" ] } [${ json[ "bots" ] }]` );

			//
			const list = $( "#players ul" );
			const players = json[ "players_list" ];

			list.empty();

			for ( const indice in players )
			{
				list.append( `<li>[${ indice }] ${ players[ indice ][ "Name" ] }</li>` );
			}
		} )
		.fail( function ( self, _status, error )
		{
			console.log( "fail" );
		} );
}

setInterval( function ()
{
	retrieveRemoteData();
}, 5000 );