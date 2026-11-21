// Importation de la feuille de style.
import "../../styles/desktop/statistics.scss";

// Importation des fonctions et constantes communes.
import "../global";

// Importation des dépendances externes.
import Chart from "chart.js/auto";
import "chartjs-adapter-date-fns";

//
// Polices des axes réduites sur les graphiques étroits (téléphones)
//  pour éviter que les titres des axes soient tronqués.
//
const isPhone = window.matchMedia( "(max-width: 768px)" ).matches;
const isNarrowChart = ( context: { chart: Chart } ) => context.chart.width < 500;
const tickFont = ( context: { chart: Chart } ) => ( { size: isNarrowChart( context ) ? 12 : 16 } );
const titleFont = {
	size: isPhone ? 14 : 20,
	family: "Roboto",
	weight: "bold" as const
};

//
// Permet de créer un graphique pour illustrer le nombre total
//  de joueurs en fonction du temps.
//
document.fonts.ready.then( () =>
{
	const playerChart = new Chart( "player_count", {
		type: "line",
		data: {
			labels: window.time_data,
			datasets: [
				// Données brutes transmises par la base de données.
				{
					data: window.player_count_data,
					fill: true,
					borderWidth: 3,
					borderColor: "#f59a23",
					hoverBorderColor: "#f59a23",
					hoverBackgroundColor: "#f2f2f2"
				}
			]
		},
		options: {
			responsive: true,
			aspectRatio: isPhone ? 1 : 2,
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
						font: tickFont,
						stepSize: 1
					},
					title: {
						display: true,
						text: window.utc_time,
						color: "#f59a23",
						font: titleFont,
						padding: { top: 15, bottom: 0 }
					}
				},
				y: {
					// Axe Y : nombre de joueurs compris entre 0 et 128 (limitation des serveurs).
					min: 0,
					max: 128,
					grid: {
						color: "#797979"
					},
					ticks: {
						color: "#f59a23",
						font: tickFont,
						stepSize: 16
					},
					title: {
						display: true,
						text: window.player_count,
						color: "#f59a23",
						font: titleFont,
						padding: { top: 0, bottom: 10 }
					}
				}
			}
		}
	} );

	playerChart.update();

	//
	// Permet de créer un graphique pour illustrer les statistiques
	//  d'utilisation du serveur en fonction du temps.
	//
	const serverChart = new Chart( "server_usage", {
		type: "line",
		data: {
			labels: window.time_data,
			datasets: [
				// Données brutes transmises par la base de données.
				{
					data: window.cpu_usage_data,
					label: window.cpu_usage,
					borderWidth: 3,
					borderColor: "#ed431d",
					backgroundColor: "#ed431d",
					hoverBorderColor: "#ed431d",
					hoverBackgroundColor: "#f2f2f2"
				},
				{
					data: window.tick_rate_data,
					label: window.tick_rate,
					borderWidth: 3,
					borderColor: "#016fa0",
					backgroundColor: "#016fa0",
					hoverBorderColor: "#016fa0",
					hoverBackgroundColor: "#f2f2f2"
				}
			]
		},
		options: {
			responsive: true,
			aspectRatio: isPhone ? 1 : 2,
			interaction: {
				// Mode d'interaction avec l'axe X (sélection du point le plus proche).
				mode: "nearest",
				intersect: false
			},
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
						font: tickFont,
						stepSize: 1
					},
					title: {
						display: true,
						text: window.utc_time,
						color: "#f59a23",
						font: titleFont,
						padding: { top: 15, bottom: 0 }
					}
				},
				y: {
					// Axe Y : taux de rafraîchissement (tickrate)
					min: 0,
					max: 128,
					grid: {
						color: "#797979"
					},
					ticks: {
						color: "#f59a23",
						font: tickFont
					},
					title: {
						display: true,
						text: window.fps_usage,
						color: "#f59a23",
						font: titleFont,
						padding: { top: 0, bottom: 10 }
					}
				}
			}
		}
	} );

	serverChart.update();
} );