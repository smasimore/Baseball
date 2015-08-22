function drawSimPerfCharts(data_by_year, labels_by_year) {
    for (var id in data_by_year) {
        drawSimPerfChart('perf_' + id, labels_by_year[id], data_by_year[id]);
    }
}

function drawSimPerfChart(id, label, data) {
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
            y: _roundToHundredth(data[bin]['vegas_home_pct']),
            samples: data[bin]['num_games']}
        );
        sim_values.push({
            type: 'Sim',
            y: _roundToHundredth(data[bin]['sim_home_pct']),
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

function drawSimBetChart(id, label, data) {
    var cumulative_payout = [];
    var x_values = [];

    for (var date in data) {
        var date_data = data[date];
        var net_payout = _roundToHundredth(date_data['cumulative_payout']);
        var total_bet = _roundToHundredth(date_data['cumulative_bet_amount']);
        cumulative_payout.push({
            type: 'Cumulative Payout',
            y: net_payout,
            total_bet: total_bet,
            roi: _roundToHundredth(net_payout / total_bet * 100),
            perc_games_bet_on: _roundToHundredth(
                date_data['cumulative_num_games_bet'] /
                date_data['cumulative_num_games']
            ),
            perc_games_won: _roundToHundredth(
                date_data['cumulatiave_num_games_winner'] /
                date_data['cumulative_num_games_bet']
            )
        });

        x_values.push(date);
    }

    var num_datapoints = Object.keys(data).length;
    var min_tick_intervals = num_datapoints / 6;

    var chart = new Highcharts.Chart({
        chart: {
            type: 'line',
            renderTo: id
        },
        title: {
            text: label
        },
        xAxis: {
            categories: x_values,
            minTickInterval: min_tick_intervals
        },
        yAxis: {
            //min: 0,
            //max: 100,
            title: {
                text: '$$'
            },
            plotLines: [{
                value: 0,
                width: 1,
                color: '#808080'
            }]
        },
        tooltip: {
            formatter: function() {return ' ' + 
                'Net Gain: $' + _numberWithCommas(this.point.y) + '<br />' +
                'Total Bet Amount: $' + _numberWithCommas(this.point.total_bet) 
                    + '<br />' +
                'ROI: ' + this.point.roi + '% <br />' + 
                '% Games Bet On: ' + this.point.perc_games_bet_on + '% <br />' +
                '% Games Won: ' + this.point.perc_games_won + '%';
            }
        },
        series: [
            {
                name: 'Net Payout',
                data: cumulative_payout,
                turboThreshold: 10000
            }
        ]
    });
}

function _roundToHundredth(input) {
    return Math.round(input*100)/100;
}

function _numberWithCommas(x) {
    return x.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
}
