<?php
	//
	// Contrôleur de gestion de la page d'administration.
	//

	// On vérifie si l'utilisateur est considéré comme un administrateur.
	if ($_SESSION["user_level"] !== "admin")
	{
		header("Location: ?target=dashboard");
		exit();
	}

	//
	// Permet de filter les données présentes en paramètres POST
	//	afin de récupérer celles liées à un identifiant unique.
	// 	Source : https://github.com/FlorianLeChat/Portfolio/blob/cf930999d5740575ff606998e1e9f7748f2355ea/includes/controllers/database.php#L83-L112
	//
	function filterPostData(string $identifier, array $data): array
	{
		// Filtrage des données par l'identifiant présumé.
		$data = array_filter($data, function($key)
			use (&$identifier) // Utiliser « global $identifier; » me retourne une valeur étrange...
			{
				return str_contains($key, "_$identifier");
			},
		ARRAY_FILTER_USE_KEY);

		// Suppression de l'identifiant sur le nom nom des clés.
		// 	Exemple : "source_string_25" => "source_string".
		foreach ($data as $key => $value)
		{
			// Remplacement du nom de la clé.
			$name = str_replace("_$identifier", "", $key);

			// Ajout d'une nouvelle définition.
			$data[$name] = $value;

			// Suppression de l'ancienne entrée.
			unset($data[$key]);
		}

		return $data;
	}

	//
	// Permet d'effectuer l'insertion d'une ligne quelconque dans
	//	la base de données.
	// 	Source : https://github.com/FlorianLeChat/Portfolio/blob/cf930999d5740575ff606998e1e9f7748f2355ea/includes/controllers/database.php#L114-L127
	//
	function insertRow(string $table, array $fields, array $values): void
	{
		global $server;

		// Génération des champs pour la requête suivante.
		$fields_parameters = implode(", ", $fields);
		$values_parameters = implode(", ", array_fill(0, count($values), "?")); // Résultat : "?, ?, ?, ..."

		// Exécution de la requête d'insertion.
		$query = $server->connector->prepare("INSERT IGNORE INTO `$table` ($fields_parameters) VALUES ($values_parameters);");
		$query->execute($values);
	}

	//
	// Permet de mettre à jour les données actuelles d'une ligne quelconque
	//	dans la base de données.
	//	Source : https://github.com/FlorianLeChat/Portfolio/blob/cf930999d5740575ff606998e1e9f7748f2355ea/includes/controllers/database.php#L129-L161
	//
	function updateRow(string $identifier, string $table, array $fields, array $values): void
	{
		global $server;

		// Génération du champs pour la requête suivante.
		$length = count($fields) - 1;
		$parameters = "";

		foreach ($fields as $indice => $field)
		{
			$parameters .= $field . " = ?";

			if ($indice < $length)
			{
				// Seul le dernier paramètre ne possède pas de délimiteur.
				$parameters .= ", ";
			}
		}

		// Récupération de la valeur initiale avant modification.
		$where_data = $server->connector->query("SELECT * FROM `$table` LIMIT 1 OFFSET $identifier;")->fetch();

		$where_field = array_key_first($where_data);	// Nom de la première colonne.
		$where_value = $where_data[$where_field];		// Valeur de la première colonne.

		$values[] = $where_value;						// Insertion dans les paramètres de la requête.

		// Exécution de la requête de mise à jour.
		$query = $server->connector->prepare("UPDATE IGNORE `$table` SET $parameters WHERE $where_field = ?;");
		$query->execute($values);
	}

	//
	// Permet de supprimer une ligne quelconque dans la base de données.
	//	Source : https://github.com/FlorianLeChat/Portfolio/blob/cf930999d5740575ff606998e1e9f7748f2355ea/includes/controllers/database.php#L163-L177
	//
	function deleteRow(string $identifier, string $table): void
	{
		global $server;

		// Récupération de la valeur initiale avant modification.
		$where_data = $server->connector->query("SELECT * FROM `$table` LIMIT 1 OFFSET $identifier;")->fetch();

		$where_field = array_key_first($where_data);	// Nom de la première colonne.
		$where_value = $where_data[$where_field];		// Valeur de la première colonne.

		// Exécution de la requête de suppression.
		$query = $server->connector->prepare("DELETE FROM `$table` WHERE $where_field = ?;");
		$query->execute([$where_value]);
	}

	//
	// Permet de calculer le décalage qui doit être appliqué à la requête SQL
	//	pour afficher toutes les lignes d'une table.
	//	Source : https://github.com/FlorianLeChat/Portfolio/blob/cf930999d5740575ff606998e1e9f7748f2355ea/includes/controllers/database.php#L179-L218
	//
	function computeOffset(int $count, string $previous_table, string $requested_table, int $offset): int
	{
		global $server;

		// On vérifie si la table précédente est la même que celle demandée.
		if ($previous_table === $requested_table)
		{
			// On calcule alors la prochaine tranche de résultats.
			$next_chunk = $offset + $count;

			// On récupère ensuite le nombre limite de résultats.
			$table_size = $server->connector->query("SELECT COUNT(*) FROM `$requested_table`;")->fetch();
			$table_size = $table_size["COUNT(*)"];

			if ($next_chunk > $table_size)
			{
				// Risque de dépassement du nombre de résultats, on calcule le
				//	nombre restants de résultats. Si cette valeur est nulle, on
				//	procède à une réinitialisation du compteur.
				$left = $table_size - $next_chunk;
				$offset = $left > 0 ? $left : 0;
			}
			else
			{
				// Il reste encore des résultats pour la prochaine tranche, on
				//	continue de procéder à un décalage des résultats.
				$offset = $next_chunk;
			}
		}
		else
		{
			// Dans le cas contraire, on réinitialise ce décalage.
			$offset = 0;
		}

		// On retourne enfin le décalage calculé.
		return $offset;
	}

	//
	// Permet de gérer les demandes de changements dans la base de données.
	//	Source : https://github.com/FlorianLeChat/Portfolio/blob/cf930999d5740575ff606998e1e9f7748f2355ea/includes/controllers/database.php#L220-L264
	//
	function requestChange(array $identifier, string $table, array $data): void
	{
		// On récupère d'abord les données associées à l'identifiant unique
		//	présumé de la table.
		$identifier = filter_var(array_key_first($identifier), FILTER_SANITIZE_NUMBER_INT);
		$data = filterPostData($identifier, $data);

		// On récupère ensuite le type de modification sur la base de données.
		$type = array_key_last($data);

		unset($data[$type]);

		// On récupère après toutes les clés et les valeurs des données.
		$fields = array_keys($data);
		$values = array_values($data);

		if ($table === "users" && !str_starts_with($values[2], "$2y$"))
		{
			// Si c'est une action dans la table utilisateurs, on vérifie si
			//	le champ de mot passe doit être hash.
			$values[2] = password_hash($values[2], PASSWORD_DEFAULT);
		}

		// On effectue enfin l'action à réaliser.
		switch ($type)
		{
			// Insertion d'une ligne.
			case "add":
				insertRow($table, $fields, $values);
				break;

			// Mise à jour d'une ligne.
			case "update":
				updateRow($identifier, $table, $fields, $values);
				break;

			// Suppression d'une ligne.
			case "remove":
				deleteRow($identifier, $table);
				break;
		}
	}

	//
	// Permet de créer la structure HTML des données (lignes et colonnes)
	//	représentatives d'une table SQL.
	//	Source : https://github.com/FlorianLeChat/Portfolio/blob/cf930999d5740575ff606998e1e9f7748f2355ea/includes/controllers/database.php#L289-L382
	//
	function generateHTMLData(int $count, string $table, bool $needOffset): string
	{
		global $server;

		// On calcule d'abord un décalage pour limiter les résultats afin
		//	d'améliorer les performances d'affichage.
		// 	Note : ce décalage est uniquement appliqué lors d'une visualisation
		//		et non pas pour les autres actions.
		if ($needOffset)
		{
			// La page a demandé le modifier le décalage.
			$offset = computeOffset($count, $_SESSION["selected_table"] ?? "", $table, $_SESSION["table_offset"] ?? 0);
		}
		else
		{
			// Dans le cas contraire, on reprend la dernière tranche.
			$offset = $_SESSION["table_offset"] ?? 0;
		}

		$_SESSION["table_offset"] = $offset;
		$_SESSION["selected_table"] = $table;

		// On exécute les requêtes SQL grâce aux paramètres obtenus précédemment.
		$rows = $server->connector->query("SELECT * FROM $table LIMIT $count OFFSET $offset;")->fetchAll();
		$columns = $server->connector->query("SHOW COLUMNS FROM $table;")->fetchAll();

		// On fabrique après la structure HTML pour l'en-tête de la table.
		$html = "<thead><tr>";

		foreach ($columns as $column)
		{
			$html .= "<th>" . $column["Field"] . "</th>";
		}

		$html .= "<th></th><tr/></thead><tbody>";

		// On fabrique ensuite la structure HTML pour chaque ligne.
		foreach ($rows as $row)
		{
			// Chaque colonne doit être séparé entre elles.
			// 	Note : les noms des champs de saisies sont composés de façon
			//		à pouvoir être identifié indépendamment des autres.
			$html .= "<tr>";
			$identifier = null;

			foreach ($row as $key => $value)
			{
				if (empty($identifier))
				{
					// On met en mémoire l'identifiant unique (présumé) de la
					//	colonne pour l'action du formulaire.
					$identifier = $offset;
				}

				$html .= "<td><textarea name=\"" . $key . "_" . $offset . "\">$value</textarea></td>";
			}

			// Création des actionneurs pour le formulaire.
			$html .= "
					<td>
						<input type=\"submit\" name=\"update_$identifier\" value=\"Éditer\" />
					</td>
					<td>
						<input type=\"submit\" name=\"remove_$identifier\" value=\"Supprimer\" />
					</td>
				</tr>
			";

			$offset++;
		}

		// On fabrique enfin une dernière ligne de champs pour ajouter une
		//	information dans la table.
		$html .= "<tr>";
		$length = count($rows);

		for ($indice = 0; $indice < count($columns); $indice++)
		{
			$html .= "<td><textarea name=\"" . $columns[$indice]["Field"] . "_" . $length . "\"></textarea></td>";
		}

		// Création des actionneurs pour le formulaire.
		$html .= "
				<td>
					<input type=\"submit\" name=\"add_$length\" value=\"Ajouter\" />
				</td>
			</tr>
		</tbody>
		";

		return $html;
	}

	// On vérifie après si la requête actuelle est de type POST.
	//	Source : https://github.com/FlorianLeChat/Portfolio/blob/cf930999d5740575ff606998e1e9f7748f2355ea/admin/index.php#L26-L56
	if ($_SERVER["REQUEST_METHOD"] === "POST")
	{
		// On tente de récupérer la table actuellement sélectionnée.
		// 	Note : lors d'une édition ou d'une suppression, l'information
		//		n'est plus présente en paramètre POST et doit donc être
		//		récupéré dans les données de la SESSION.
		$table = $_POST["show"] ?? $_SESSION["selected_table"] ?? "";

		// On récupère l'identifiant unique présumé de la table avant
		//	d'y récupérer les données associées.
		// 	Note : le numéro se trouve en suffixe du nom de l'action.
		//	Exemple : "add_25", identifiant 25.
		$identifier = array_filter($_POST, function($key)
		{
			return (str_contains($key, "add_") || str_contains($key, "update_") || str_contains($key, "remove_"));
		}, ARRAY_FILTER_USE_KEY);

		if (is_array($identifier) && count($identifier) > 0)
		{
			// On effectue alors une requête de changement.
			requestChange($identifier, $table, $_POST);
		}

		// On réalise ensuite l'affichage de tout le contenu de la table.
		//	Note : cette action se réalisera automatique après une action
		//		sur la base de données.
		if (!empty($table))
		{
			$result = generateHTMLData(25, $table, empty($identifier));
		}
	}

	// On inclut enfin les paramètres du moteur TWIG pour la création
	//	finale de la page.
	$parameters = [

		// Génération de toutes les tables de la base de données.
		"admin_tables" => $server->connector->query("SHOW TABLES;")->fetchAll(),

		// Code HTML de l'affichage des tables.
		"admin_result" => $result ?? ""

	];
?>