const Encore = require( "@symfony/webpack-encore" );

if ( !Encore.isRuntimeEnvironmentConfigured() )
{
	Encore.configureRuntimeEnvironment( process.env.NODE_ENV || "dev" );
}

Encore
	.setOutputPath( "public/build/" )
	.setPublicPath( "/build" )
	.addEntry( "global", "./assets/app.js" )
	.addEntry( "actions", "./assets/scripts/actions.js" )
	.addEntry( "admin", "./assets/scripts/admin.js" )
	.addEntry( "configuration", "./assets/scripts/configuration.js" )
	.addEntry( "console", "./assets/scripts/console.js" )
	.addEntry( "dashboard", "./assets/scripts/dashboard.js" )
	.addEntry( "help", "./assets/scripts/help.js" )
	.addEntry( "index", "./assets/scripts/index.js" )
	.addEntry( "legal", "./assets/scripts/legal.js" )
	.addEntry( "statistics", "./assets/scripts/statistics.js" )
	.addEntry( "tasks", "./assets/scripts/tasks.js" )
	.addEntry( "user", "./assets/scripts/user.js" )
	.enableStimulusBridge( "./assets/controllers.json" )
	.splitEntryChunks()
	.autoProvidejQuery()
	.enableSingleRuntimeChunk()
	.cleanupOutputBeforeBuild()
	.enableBuildNotifications()
	.enableSourceMaps( !Encore.isProduction() )
	.enableVersioning( Encore.isProduction() )
	.configureManifestPlugin( options =>
	{
		options.fileName = "webpack-manifest.json";
	} )
	.configureBabelPresetEnv( ( config ) =>
	{
		config.useBuiltIns = "usage";
		config.corejs = "3.23";
	} )
	.copyFiles( [
		{ from: "./assets/favicons", to: "favicons/[path][name].[hash:8].[ext]" },
		{ from: "./assets/images", to: "images/[path][name].[hash:8].[ext]" },
		{ from: "./assets/videos", to: "videos/[path][name].[hash:8].[ext]" }
	] )
	.configureImageRule( {
		type: "asset",
	} )
	.configureFontRule( {
		type: "asset",
	} )
	.enableSassLoader()
	.enableIntegrityHashes( Encore.isProduction() );

module.exports = Encore.getWebpackConfig();