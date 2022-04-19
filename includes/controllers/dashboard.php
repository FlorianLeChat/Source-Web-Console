<?php
	//
	$parameters = [

		"dashboard_instances" => $server->getInstances($_SESSION["identifier"])

	];

	//
	$function = new \Twig\TwigFunction("getNameByGameID", function(int $identifier, string $fallback)
	{
		global $server;
		return $server->getNameByGameID($identifier, $fallback);
	});

	$twig->addFunction($function);

	//
	try
	{
		//
		$server->connectInstance("localhost", 27015, "my_awesome_password");

		//
		var_dump($server->query->GetInfo());
		var_dump($server->query->GetPlayers());
		var_dump($server->query->GetRules());
		var_dump($server->query->Rcon("say hello"));
	}
	catch (Exception $error)
	{
		//
		echo($error->getMessage());
	}
	finally
	{
		//
		$server->query->Disconnect();
	}
?>