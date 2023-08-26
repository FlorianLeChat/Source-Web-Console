const path = require( "path" );
const local = path.resolve( process.cwd(), ".env.local" );
const Encore = require( "@symfony/webpack-encore" );
const fileSystem = require( "fs" );

require( "dotenv" ).config( { path: fileSystem.existsSync( local ) ? local : undefined } );

if ( !Encore.isRuntimeEnvironmentConfigured() )
{
	Encore.configureRuntimeEnvironment( process.env.NODE_ENV || "dev" );
}

Encore.configureDefinePlugin( ( options ) =>
{
	// Définition des variables d'environnement personnalisées.
	options[ "process.env.ANALYTICS_ENABLED" ] = JSON.stringify( process.env.ANALYTICS_ENABLED );
	options[ "process.env.RECAPTCHA_ENABLED" ] = JSON.stringify( process.env.RECAPTCHA_ENABLED );
} );

Encore
	.setOutputPath( "public/build/" )
	.setPublicPath( "/build" )
	.addEntry( "actions", "./assets/scripts/pages/actions.ts" )
	.addEntry( "configuration", "./assets/scripts/pages/configuration.ts" )
	.addEntry( "console", "./assets/scripts/pages/console.ts" )
	.addEntry( "dashboard", "./assets/scripts/pages/dashboard.ts" )
	.addEntry( "help", "./assets/scripts/pages/help.ts" )
	.addEntry( "index", "./assets/scripts/pages/index.ts" )
	.addEntry( "legal", "./assets/scripts/pages/legal.ts" )
	.addEntry( "statistics", "./assets/scripts/pages/statistics.ts" )
	.addEntry( "tasks", "./assets/scripts/pages/tasks.ts" )
	.addEntry( "user", "./assets/scripts/pages/user.ts" )
	.splitEntryChunks()
	.autoProvidejQuery()
	.enableTypeScriptLoader()
	.enableSingleRuntimeChunk()
	.cleanupOutputBeforeBuild()
	.enableBuildNotifications()
	.enableForkedTypeScriptTypesChecking()
	.enableSourceMaps( !Encore.isProduction() )
	.enableVersioning( Encore.isProduction() )
	.configureFilenames( {
		js: "[name].[contenthash:8].min.js",
		css: "[name].[contenthash:8].min.css"
	} )
	.configureManifestPlugin( ( options ) =>
	{
		options.fileName = "webpack-manifest.json";
	} )
	.configureBabelPresetEnv( ( config ) =>
	{
		config.useBuiltIns = "usage";
		config.corejs = "3.32";
	} )
	.copyFiles( [
		{ from: "./assets/favicons", to: "favicons/[path][name].[ext]" }, // https://github.com/symfony/webpack-encore/issues/796#issuecomment-653091438
		{ from: "./assets/fonts", to: "fonts/[path][name].[hash:8].[ext]" },
		{ from: "./assets/images", to: "favicons/[path][name].[ext]", includeSubdirectories: false }, // Voir commentaire précédent.
		{ from: "./assets/images", to: "images/[path][name].[hash:8].[ext]" },
		{ from: "./assets/videos", to: "videos/[path][name].[hash:8].[ext]" }
	] )
	.configureImageRule( {
		type: "asset"
	} )
	.configureFontRule( {
		type: "asset"
	} )
	.enableSassLoader()
	.enableIntegrityHashes( Encore.isProduction() );

module.exports = Encore.getWebpackConfig();