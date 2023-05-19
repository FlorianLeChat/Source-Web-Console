const Encore = require( "@symfony/webpack-encore" );

if ( !Encore.isRuntimeEnvironmentConfigured() )
{
	Encore.configureRuntimeEnvironment( process.env.NODE_ENV || "dev" );
}

Encore
	.setOutputPath( "public/build/" )
	.setPublicPath( "/build" )
	.addEntry( "actions", "./assets/scripts/pages/actions.js" )
	.addEntry( "admin", "./assets/scripts/pages/admin.js" )
	.addEntry( "configuration", "./assets/scripts/pages/configuration.js" )
	.addEntry( "console", "./assets/scripts/pages/console.js" )
	.addEntry( "dashboard", "./assets/scripts/pages/dashboard.js" )
	.addEntry( "help", "./assets/scripts/pages/help.js" )
	.addEntry( "index", "./assets/scripts/pages/index.js" )
	.addEntry( "legal", "./assets/scripts/pages/legal.js" )
	.addEntry( "statistics", "./assets/scripts/pages/statistics.js" )
	.addEntry( "tasks", "./assets/scripts/pages/tasks.js" )
	.addEntry( "user", "./assets/scripts/pages/user.js" )
	.splitEntryChunks()
	.autoProvidejQuery()
	.enableSingleRuntimeChunk()
	.cleanupOutputBeforeBuild()
	.enableBuildNotifications()
	.enableSourceMaps( !Encore.isProduction() )
	.enableVersioning( Encore.isProduction() )
	.configureManifestPlugin( ( options ) =>
	{
		options.fileName = "webpack-manifest.json";
	} )
	.configureBabelPresetEnv( ( config ) =>
	{
		config.useBuiltIns = "usage";
		config.corejs = "3.23";
	} )
	.copyFiles( [
		{ from: "./assets/favicons", to: "favicons/[path][name].[ext]" }, // https://github.com/symfony/webpack-encore/issues/796#issuecomment-653091438
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