function drawHistogram() {
    console.log(type);
    console.log(hist_actual);

    var values = [];
    var bin_min = 100;
    var bin_max = 0;
    for (var bin in hist_actual) {
        if (hist_actual[bin]) {
            if (bin < bin_min) {
                bin_min = +bin;
            }
            if (bin > bin_max) {
                bin_max = +bin;
            }
            values.push(hist_actual[bin]);
        }
    }

    var x_values = [];
    for (var i = bin_min; i <= bin_max; i += 5) {
        x_values.push(i);
    }

    var chart = new Highcharts.Chart({
        chart: {
            type: 'column',
            renderTo: type
        },
        title: {
            text: type
        },
        xAxis: {
            categories: x_values
        },
        yAxis: {
            min: 0,
            title: {
                text: 'Actual'
            }
        },
        tooltip: {
            headerFormat:
                '<span style="font-size:10px">{point.key}</span><table>',
            pointFormat:
                '<tr>' +
                    '<td style="color:{series.color};padding:0">' +
                        '{series.name}: ' +
                    '</td>' +
                '<td style="padding:0"><b>{point.y:.1f} mm</b></td></tr>',
            footerFormat: '</table>'
        },
        plotOptions: {
            column: {
                pointPadding: 0.2,
                borderWidth: 0
            }
        },
        series: [
            {
                name: type,
                data: values

            }
        ]
    });
}
