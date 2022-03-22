//
// Permet de créer un graphique pour illustrer le nombre total
//	de joueurs en fonction du temps.
//
const player_chart = $( "#player_count" );

new Chart( player_chart,
	{
		type: 'bar',
		data: {
			labels: [ 'Red', 'Blue', 'Yellow', 'Green', 'Purple', 'Orange' ],
			datasets: [ {
				label: '# of Votes',
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

//
//
//
const cpu_usage = $( "#cpu_usage" );

new Chart( cpu_usage,
	{
		type: 'bar',
		data: {
			labels: [ 'Red', 'Blue', 'Yellow', 'Green', 'Purple', 'Orange' ],
			datasets: [ {
				label: '# of Votes',
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

//
//
//
const memory_usage = $( "#memory_usage" );

new Chart( memory_usage,
	{
		type: 'bar',
		data: {
			labels: [ 'Red', 'Blue', 'Yellow', 'Green', 'Purple', 'Orange' ],
			datasets: [ {
				label: '# of Votes',
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