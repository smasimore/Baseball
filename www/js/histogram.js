var type;
var hist;
var samples;

function drawHistograms(data, sample) {
    hist_data = data['hist'];
    sample_data = data['sample'];
    for (var key in hist_data) {
        type = key;
        hist = hist_data[key];
        samples = sample_data[key];
        drawHistogram();
    }
}


function drawHistogram() {
    var values = [];
    var bin_min = 100;
    var bin_max = 0;
    for (var bin in hist) {
        if (samples[bin]) {
            if (bin < bin_min) {
                bin_min = +bin;
            }
            if (bin > bin_max) {
                bin_max = +bin;
            }
            values.push({
                y: Math.round(hist[bin]),
                samples: samples[bin]});
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
            max: 100,
            title: {
                text: 'Actual'
            }
        },
        tooltip: {
            formatter: function() {return ' ' +
                'Bin: ' + this.point.category + '<br />' +
                'Actual: ' + this.point.y + '<br />' +
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
                        return this.point.y;
                    }
                }
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
