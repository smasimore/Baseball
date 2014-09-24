window.setInterval(dynamically_load, 1000);

//Making request script available
var Head = document.getElementsByTagName('head')[0];
var Request = document.createElement('script');
Request.src='js/request.js';
Request.setAttribute('type','text/javascript');
Head.appendChild(Request);

function dynamically_load() {
  var name = get('name');
  var container = document.getElementById('error_list');
  var length = container.childNodes.length;
  var url = 'ajax/load_log.php?name=' + name + '&length=' + length;
  console.log(Request);
  // From request.js
  req = getRequest(url, load_success);
};

function get(name){
   if(name=(new RegExp('[?&]'+encodeURIComponent(name)+'=([^&]*)')).exec(location.search))
      return decodeURIComponent(name[1]);
};

function load_success(responseText) {
  var container = document.getElementById('error_list');
  container.innerHTML = container.innerHTML + responseText;
};

function highlight(row) {
  highlighted_rows = document.getElementsByClassName('selected_list');
  for (var i=0; i<highlighted_rows.length; i++) {
    var last_char = +highlighted_rows[i].id.slice(-1);
    if (last_char % 2 == 0) {
      highlighted_rows[i].className = 'even_list';
    } else {
      highlighted_rows[i].className = 'odd_list';
    }
  }

  row.className = 'selected_list';
};

function update_details(row) {
  var detail_element = document.getElementById('error_details');
  detail_element.style.display = 'block';
  var details = row.innerHTML;
  var detail_string = '';
  if (details.indexOf("array") == 0) {
    var prefix_length = ('array - ').length;
    details = JSON.parse(details.slice(prefix_length));

    // Get length of details. If > 20, shorten.
    var details_length = 0;
    for (var i in details) {
      details_length++;
    }
    if (details_length > 20) {
      var details;
      var counter = 0;
      for (var j in details) {
        if (counter > 20) {
          delete details[j];
        }
        counter++;
      }
      details["..."] = "...";
    }

    detail_string = _addArray(details, '');

  } else {
    var hyphen_loc = details.indexOf('- ');
    detail_string = details.slice(hyphen_loc + 2);
  }
  detail_element.innerHTML = detail_string;
};

function _addArray(details, detail_string) {
  detail_string = detail_string + "<font color='red'> array ( </font>" 
    + "<div style='margin-left: 50px'>";
  for (var key in details) {
    detail_string = detail_string + key + ' => '; 
    if (details[key] instanceof Array || details[key] instanceof Object) {
      detail_string = _addArray(details[key], detail_string);
    } else {
      detail_string = detail_string + details[key] + '<br />';
    }
  }
  return detail_string + "</div> <font color='red'>)</font> <br />";
};
      
function scroll_to_end() {
  console.log('scroll');
};
