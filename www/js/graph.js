var maxYSeries = 5;

// If you want more than 5 series, need to add more colors.
var colors = [
  "rgba(151,187,205,1)", 
  "rgba(0, 113, 88, 1)",
  "rgba(168, 41, 49, 1)",
  "rgba(58, 117, 49, 1)",
  "rgba(207, 208, 0, 1)"
];

function displayLineChart(id, graph_x, graph_y) {
  var zero = [];
  for (var i = 0; i < graph_x.length; i++) {
    zero.push(0);
  }

  var zero_dataset = [{
    fillColor : "rgba(0,0,0,0)",
    strokeColor : "rgba(220,220,220,1)",
    pointColor : "rgba(220,220,220,1)",
    pointStrokeColor : "#fff",
    data : zero
  }];


  // Allows us to accept multiple y-series.
  var y_series = [];
  for (var j = 2; j < arguments.length; j++) {
    // Only allow 5 series. Need to add to colors if 
    // want more.
    if (j === maxYSeries + 2) {
      console.log(j);
      break;
    }

    y_series.push({
      fillColor : "rgba(0,0,0,0)",
      strokeColor : colors[j - 2],
      pointColor : "rgba(151,187,205,1)",
      pointStrokeColor : "#fff",
      data : arguments[j],
    }); 
  }

  var datasets = zero_dataset.concat(y_series);

  var data = { 
    labels : graph_x,
    datasets : datasets
  }

  var options = { 
      animation: false,
      responsive: true,
      pointDot : false,
  }

  var ctx = document.getElementById(id).getContext("2d");
  var myNewChart = new Chart(ctx).Line(data, options);
};
