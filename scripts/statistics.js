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
				{
					data: [ 12, 4, 1, 0, 0, 0, 1, 3, 5, 1, 2, 4, 5, 6, 4, 15, 16, 18, 23, 45, 24, 26, 45, 34, 24 ],
					fill: true,
					borderColor: "#f59a23",
					borderWidth: 3,
					hoverBorderColor: "#797979",
					hoverBackgroundColor: "#f2f2f2"
				}
			]
		},
		options: {
			responsive: true,
			interaction: {
				mode: "nearest",
				intersect: false,
			},
			plugins: {
				legend: {
					display: false,
				}
			},
			scales: {
				x: {
					labels: [ 0, 1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14, 15, 16, 17, 18, 19, 20, 21, 21, 23 ],
					grid: {
						color: "#797979"
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
						text: "Heure UTC",
						color: "#f59a23",
						font: {
							size: 20,
							family: "Roboto",
							weight: "bold",
							lineHeight: 1.2,
						},
						padding: { top: 15, left: 0, right: 0, bottom: 0 }
					}
				},
				y: {
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
						text: "Nombre de joueurs",
						color: "#f59a23",
						font: {
							size: 20,
							family: "Roboto",
							weight: "bold",
							lineHeight: 1.2
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
		type: 'bar',
		data: {
			labels: [ 'Red', 'Blue', 'Yellow', 'Green', 'Purple', 'Orange' ],
			datasets: [ {
				label: 'Nombre de joueurs',
				data: [ 12, 19, 3, 5, 2, 3 ],
				backgroundColor: [
					'rgba(255, 99, 132, 0.2)',
					'rgba(54, 162, 235, 0.2)',
					'rgba(255, 206, 86, 0.2)',
					'rgba(75, 192, 192, 0.2)',
					'rgba(153, 102, 255, 0.2)',
					'rgba(255, 159, 64, 0.2)'
				],
				borderColor: [
					'rgba(255, 99, 132, 1)',
					'rgba(54, 162, 235, 1)',
					'rgba(255, 206, 86, 1)',
					'rgba(75, 192, 192, 1)',
					'rgba(153, 102, 255, 1)',
					'rgba(255, 159, 64, 1)'
				],
				borderWidth: 1
			} ]
		},
		options: {
			scales: {
				y: {
					beginAtZero: true
				}
			}
		}
	} );