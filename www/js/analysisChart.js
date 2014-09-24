function displayAnalysisChart(id, graph_x, y_series) {
  console.log(y_series);
  var graph_y = y_series[0];
  var zero = [];
  for (var i = 0; i < graph_x.length; i++) {
    zero.push(0);
  }

  var data = { 
    labels : graph_x,
    datasets : [ 
      {   
          fillColor : "rgba(220,220,220,0.5)",
          strokeColor : "rgba(220,220,220,1)",
          pointColor : "rgba(220,220,220,1)",
          pointStrokeColor : "#fff",
          data : zero
      },  
      {   
        fillColor : "rgba(151,187,205,0.5)",
        strokeColor : "rgba(151,187,205,1)",
        pointColor : "rgba(151,187,205,1)",
        pointStrokeColor : "#fff",
        data : graph_y 
      }   
    ]

  }

  var options = { 
      animation: false,
      responsive: true,
      pointDot : false,
  }

  var ctx = document.getElementById(id).getContext("2d");
  var myNewChart = new Chart(ctx).Line(data, options);
};
