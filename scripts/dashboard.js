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
		parent.append( `<input type=\"hidden\" name=\"client_address\" value=\"${ prompt( edit_client_address ) }\" />` );
		parent.append( `<input type=\"hidden\" name=\"client_port\" value=\"${ prompt( edit_client_port ) }\" />` );

		// Adresse IP, port et mot de passe administrateur.
		parent.append( `<input type=\"hidden\" name=\"admin_address\" value=\"${ prompt( edit_admin_address ) }\" />` );
		parent.append( `<input type=\"hidden\" name=\"admin_port\" value=\"${ prompt( edit_admin_port ) }\" />` );
		parent.append( `<input type=\"hidden\" name=\"admin_password\" value=\"${ prompt( edit_admin_password ) }\" />` );
	}

	// On force enfin la soumission du formulaire en indiquant
	//	qu'on ne doit pas demander de nouveau les informations.
	submit_edit = true;

	$( this ).click();
} );

//
// Permet de faire la récupération des informations générales de l'instance.
//
function retrieveRemoteData()
{
	// On réalise d'abord la requête AJAX.
	$.post( "includes/controllers/server_overview.php", {

		// Adresse IP du serveur.
		server_address: current_address,

		// Port de communication du serveur.
		server_port: current_port,

		// Mot de passe d'administration du serveur.
		server_password: current_password

	} )
		.done( function ( data, _status, _self )
		{
			// Une fois terminée, on affiche la réponse JSON du
			//	serveur sous forme d'une liste numérique.
			const json = JSON.parse( data );

			// On vérifie ensuite s'il ne s'agit pas d'une erreur,
			//	dans ce cas, on affiche une notification.
			if ( json.hasOwnProperty( "error" ) )
			{
				addQueuedNotification( server_fatal_error.replace( "$1", json[ "error" ] ), 1 );
				return;
			}

			// Affichage de l'état de fonctionnement.
			const state_field = $( "[data-field = state]" );

			if ( json.hasOwnProperty( "gamemode" ) )
			{
				// Vérification de l'état (maintenance ou en fonctionnement).
				if ( json[ "password" ] === true )
				{
					// Serveur sécurisé par mot de passe, maintenance ou mise à jour en cours.
					state_field.html( server_service.replace( "$1", json[ "gamemode" ] ) );
				}
				else
				{
					// Serveur en fonctionnement standard.
					state_field.html( server_running.replace( "$1", json[ "gamemode" ] ) );
				}
			}
			else
			{
				// Information par défaut.
				state_field.html( server_no_data );
			}

			// Affichage de la carte actuelle.
			const maps_field = $( "[data-field = map]" );

			if ( json.hasOwnProperty( "maps" ) )
			{
				// Information du serveur.
				maps_field.html( json[ "maps" ] );
			}
			else
			{
				// Information par défaut.
				maps_field.html( "gm_source" );
			}

			// Affichage du nombre de joueurs/clients.
			const players_field = $( "[data-field = players]" );

			if ( json.hasOwnProperty( "players" ) && json.hasOwnProperty( "max_players" ) && json.hasOwnProperty( "bots" ) )
			{
				// Information du serveur.
				players_field.html( `${ json[ "players" ] } / ${ json[ "max_players" ] } [${ json[ "bots" ] }]` );
			}
			else
			{
				// Information par défaut.
				players_field.html( "0 / 0 [0]" );
			}

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
			// Dans le cas contraire, on affiche une notification
			//	d'échec avec les informations à notre disposition.
			addQueuedNotification( server_fatal_error.replace( "$1", getStatusText( error, self.status ) ), 1 );
		} );
}

// Récupération des informations au démarrage.
retrieveRemoteData();

// Récupération des informations toutes les 5 secondes.
setInterval( function ()
{
	retrieveRemoteData();
}, 5000 );