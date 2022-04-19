//
// Permet d'appliquer les images en arrière-plan des serveurs
//	en fonction de leur jeu installé.
//
const servers = $( "li[data-image]" );
let indice = 1;

for ( const server of servers )
{
	$( `<style>#servers li:nth-of-type(${ indice }):before { background-image: url(${ $( server ).attr( "data-image" ) })</style>` ).appendTo( "head" );

	indice++;
}