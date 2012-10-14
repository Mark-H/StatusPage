<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <link href="css/bootstrap.min.css" rel="stylesheet">
    <style>body {padding-top: 60px;} .icon-ok, .icon-fire{text-indent: -999999px;}</style>
	
    <!--[if lt IE 9]><script src="http://html5shim.googlecode.com/svn/trunk/html5.js"></script><![endif]-->
	
	<title>Server Status Dashboard</title>
</head>
<body>
<div class="navbar navbar-inverse navbar-fixed-top">
      <div class="navbar-inner">
        <div class="container">
          <a class="btn btn-navbar" data-toggle="collapse" data-target=".nav-collapse">
            <span class="icon-bar"></span>
            <span class="icon-bar"></span>
            <span class="icon-bar"></span>
          </a>
          <a class="brand" href="#">Network Status</a>
          <div class="nav-collapse collapse">
            <ul class="nav">
              <li class="active"><a href="#">Home</a></li>
              <li><a href="http://skytoaster.com/contact">Contact</a></li>
            </ul>
          </div>
        </div>
      </div>
    </div>

    <div class="container">

	<div class="alert alert-info"><button type="button" class="close" data-dismiss="alert">x</button><strong>Head Up</strong> This page automatically refreshes every 30 second.  Hold shift to sort by multiple fields</div>
	
		<div  id="ajaxdata">
			<h2 style="text-align:center">Fetching Live Data, Stand By...</h2><div class="progress progress-striped active"><div class="bar" style="width: 50%;"></div></div>
		</div>
	
	</div>
	
	<script src="https://ajax.googleapis.com/ajax/libs/jquery/1.8.1/jquery.min.js"></script>
	<script src="js/bootstrap.min.js"></script>
	<!-- <script src="js/ajaxdata.js"></script>
	<script>$(document).ready(function() { $("table").tablesorter( {sortList: [[0,1], [1,0]]} ); } ); </script>-->
	<script src="js/jquery.tablesorter.min.js"></script>
	<script>$(document).ready(function() {
	 var auto_refresh,
		 errors = 0,
		 alert  = $('<div class="alert alert-error fade">Can\'t hear the server from here.</div>');

	 setInterval(auto_refresh = function() {
	  $('#ajaxdata').load('data.php', function(response, status, xhr) {
	   if (status == 'error') {
		if (++errors == 2)
		 alert.prependTo($('body > .container').first()).addClass('in');

		return;
	   } else {
		errors = 0;
		alert.alert('close');
	   }

	   $('#ajaxdata').fadeIn("slow");
	   $("table").tablesorter();
	   $("table").trigger("update");
	   var sorting = [[0,1],[1,0]];
	   $("table").trigger("sorton",[sorting]);
	  });
	 }, 5000); // refresh every 10000 milliseconds
	 
	 // trigger refresh without waiting
	 auto_refresh();
	});
	</script>
	
</body>
</html>
