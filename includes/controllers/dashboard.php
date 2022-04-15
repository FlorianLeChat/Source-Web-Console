<?php
	//
	$parameters = [

		$file . "_instances" => $server->getInstances($_SESSION["identifier"])

	];

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
		echo $error->getMessage();
	}
	finally
	{
		//
		$server->query->Disconnect();
	}
?>