function drawCharts(data_by_year) {
    for (var id in data_by_year) {
        drawChart(id, 'FILL ME', data_by_year[id]);
    }
}

function drawChart(id, label, data) {
    var actual_values = [];
    var vegas_values = [];
    var sim_values = [];
    for (var bin in data) {
        actual_values.push({
            type: 'Actual',
            y: _roundToHundredth(data[bin]['actual_win_pct']),
            samples: data[bin]['num_games']}
        );
        vegas_values.push({
            type: 'Vegas',
            y: _roundToHundredth(data[bin]['vegas_win_pct']),
            samples: data[bin]['num_games']}
        );
        sim_values.push({
            type: 'Sim',
            y: _roundToHundredth(data[bin]['sim_win_pct']),
            samples: data[bin]['num_games']}
        );
    }

    var x_values = [];
    for (var i = 0; i < 100; i += 5) {
        x_values.push(i);
    }

    var chart = new Highcharts.Chart({
        chart: {
            type: 'column',
            renderTo: id
        },
        title: {
            text: label
        },
        xAxis: {
            categories: x_values
        },
        yAxis: {
            min: 0,
            max: 100,
            title: {
                text: '% Win'
            }
        },
        tooltip: {
            formatter: function() {return ' ' +
                'Type: ' + this.point.type + '<br />' +
                'Value: ' + this.point.y + '<br />' +
                'Bin: ' + this.point.category + '<br />' +
                'Sample Size: ' + this.point.samples;
            }
        },
        plotOptions: {
            column: {
                pointPadding: 0.2,
                borderWidth: 0,
            },
            series: {
                dataLabels: {
                    enabled: true,
                    formatter: function() {
                        if (this.point.y != 0) {
                            return Math.round(this.point.y);
                        }
                    }
                }
            }
        },
        series: [
            {
                name: 'Actual',
                color: '#00cc00',
                data: actual_values
            },
            {
                name: 'Sim',
                color: '#fffff',
                data: sim_values
            },
            {
                name: 'Vegas',
                color: '#cc0000',
                data: vegas_values
            }
        ]
    });
}

function _roundToHundredth(input) {
    return Math.round(input*100)/100;
}
