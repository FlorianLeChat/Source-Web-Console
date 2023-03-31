// Importation des feuilles de style.
import "../styles/desktop/statistics.scss";
import "../styles/phone/statistics.scss";
import "../styles/tablet/statistics.scss";

//
// Permet de générer les libellés présents sur les graphiques.
// 	Note : ils sont créés en partant de 24 heures en arrière.
//
const date_now = Date.now();								// Horodatage immédiat.
const time_offset = 3600 * 1000;							// Représentation d'une journée en secondes.

let labels = [];											// Enregistrement des libellés.
let date_back = new Date( date_now - ( 86400 * 1000 ) );	// Création de la date 24 heures en arrière.
date_back.setMinutes( 0, 0, 0 );							// Réinitialisation des minutes/secondes et millisecondes.
date_back = date_back.getTime();							// Récupération de l'horodatage final.

for ( let indice = 0; indice <= 24; indice++ )
{
	// On itère 24 fois pour créer toutes les heures partant
	//	de 24 heures en arrière jusqu'à maintenant.
	const date = new Date( date_back + time_offset * indice );

	labels.push( date.toISOString() );
}

//
// Permet de créer un graphique pour illustrer le nombre total
//	de joueurs en fonction du temps.
//
const player_chart = $( "#player_count" );

new Chart( player_chart,
	{
		type: "line",
		data: {
			datasets: [
				// Données brutes transmises par la base de données.
				{
					data: [ 12, 4, 1, 0, 0, 0, 1, 3, 5, 1, 2, 4, 5, 6, 4, 15, 16, 18, 23, 45, 24, 26, 45, 34, 24 ],
					fill: true,
					borderWidth: 3,
					borderColor: "#f59a23",
					hoverBorderColor: "#797979",
					hoverBackgroundColor: "#f2f2f2"
				}
			]
		},
		options: {
			responsive: true,
			interaction: {
				// Mode d'interaction avec l'axe X (sélection du point le plus proche).
				mode: "nearest",
				intersect: false
			},
			plugins: {
				legend: {
					display: false
				}
			},
			scales: {
				x: {
					// Axe X : heure UTC sous format international ISO.
					type: "time",
					labels: labels,
					grid: {
						color: "#797979"
					},
					time: {
						unit: "hour",
						tooltipFormat: "HH:mm - dd/MM/yyyy",
						displayFormats: {
							hour: "HH:00"
						}
					},
					ticks: {
						color: "#f59a23",
						font: {
							size: 16
						},
						stepSize: 1,
						beginAtZero: true
					},
					title: {
						display: true,
						text: utc_time,
						color: "#f59a23",
						font: {
							size: 20,
							family: "Roboto",
							weight: "bold"
						},
						padding: { top: 15, left: 0, right: 0, bottom: 0 }
					}
				},
				y: {
					// Axe Y : nombre de joueurs compris entre 0 et 128 (limitation des serveurs).
					max: 128,
					labels: [ 0, 128 ],
					grid: {
						color: "#797979"
					},
					ticks: {
						color: "#f59a23",
						font: {
							size: 16,
						},
						stepSize: 2,
						beginAtZero: true
					},
					title: {
						display: true,
						text: player_count,
						color: "#f59a23",
						font: {
							size: 20,
							family: "Roboto",
							weight: "bold"
						},
						padding: { top: 0, left: 0, right: 0, bottom: 10 }
					}
				}
			}
		}
	} );

//
// Permet de créer un graphique pour illustrer les statistiques
//	d'utilisation du serveur en fonction du temps.
//
const server_usage = $( "#server_usage" );

new Chart( server_usage,
	{
		type: "bar",
		data: {
			type: "time",
			labels: labels,
			grid: {
				color: "#797979"
			},
			time: {
				unit: "hour",
				tooltipFormat: "HH:mm - dd/MM/yyyy"
			},
			datasets: [
				// Données brutes transmises par la base de données.
				{
					label: cpu_usage,
					backgroundColor: "#ed431d",
					data: [ 12.4, 12.68, 25, 35, 24, 12, 10, 8, 6, 1, 36, 45, 75, 89, 100, 98, 64, 54, 62, 56, 68, 72, 41, 35, 43 ]
				},
				{
					label: ram_usage,
					backgroundColor: "#016fa0",
					data: [ 25, 26, 27, 28, 26, 25, 24, 23, 26, 27, 30, 23, 27, 28, 35, 41, 38, 36, 33, 31, 25, 36, 26, 27, 30 ]
				}
			]
		},
		options: {
			responsive: true,
			plugins: {
				// Personnalisation de la légende du graphique.
				legend: {
					display: true,
					labels: {
						color: "#f59a23",
						font: {
							size: 16,
							family: "Roboto",
							weight: "bold"
						}
					}
				}
			},
			scales: {
				x: {
					// Axe X : heure UTC sous format international ISO.
					type: "time",
					labels: labels,
					grid: {
						color: "#797979"
					},
					time: {
						unit: "hour",
						tooltipFormat: "HH:mm - dd/MM/yyyy",
						displayFormats: {
							hour: "HH:00"
						}
					},
					ticks: {
						color: "#f59a23",
						font: {
							size: 16
						},
						stepSize: 1,
						beginAtZero: true
					},
					title: {
						display: true,
						text: utc_time,
						color: "#f59a23",
						font: {
							size: 20,
							family: "Roboto",
							weight: "bold"
						},
						padding: { top: 15, left: 0, right: 0, bottom: 0 }
					}
				},
				y: {
					// Axe Y : charge d'utilisation du processeur et de la mémoire (comprise entre 0 et 100%).
					max: 100,
					labels: [ 0, 100 ],
					grid: {
						color: "#797979"
					},
					ticks: {
						color: "#f59a23",
						font: {
							size: 16,
						},
						stepSize: 5,
						beginAtZero: true
					},
					title: {
						display: true,
						text: usage_percent,
						color: "#f59a23",
						font: {
							size: 20,
							family: "Roboto",
							weight: "bold"
						},
						padding: { top: 0, left: 0, right: 0, bottom: 10 }
					}
				}
			}
		}
	} );