function expand(table) {
  for(var i=0; i<table.rows.length; i++) {
    if (table.rows[i].style.display === ""
        && table.rows[i].id !== "header") {
      table.rows[i].style.display = "none";
    } else {
      table.rows[i].style.display = "";
    }
  }
};

