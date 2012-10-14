<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Server Status Dashboard</title>

    <link href="css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            padding-top: 60px;
        }

        .icon-ok, .icon-fire {
            text-indent: -999999px;
        }

        .loading-message {
            width: 50%;
            margin: 0 25%;
            text-align: center;
        }
    </style>

    <!--[if lt IE 9]><script src="http://html5shim.googlecode.com/svn/trunk/html5.js"></script><![endif]-->
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
                    <li class="active"><a href="#">Services Uptime</a></li>
                </ul>
            </div>
        </div>
    </div>
</div>

<div class="container">

    <div class="alert alert-info">
        <button type="button" class="close" data-dismiss="alert">&times;</button>
        The table below gets automatically updated every 30 seconds, so no need to refresh the page.
    </div>

    <div id="update-progress" class="pull-right progress progress-striped active" style="width: 1.5em;">
        <div class="bar" style="width:100%"></div>
    </div>
    <p class="pull-right muted">Updated: <span id="lastupdated"><em>waiting for data</em></span>&nbsp;</p>
    <table class="table table-bordered table-striped">
        <thead>
            <tr>
                <td>Status</td>
                <td>Server or Service</td>
                <td><abbr title="Averate Response Time, based on data from the past hour.">ART</abbr></td>
            </tr>
        </thead>
        <tbody id="ajaxdata">
            <tr>
                <td colspan="3">
                    <div class="loading-message">
                        <h3 style="text-align:center">Fetching Live Data, Stand By...</h3>
                        <p class="muted center"><small>This shouldn't take long. Definitely not long enough for you to read this entire message. If you do have time to read this all, our monitoring services may be a bit slow right now. Still waiting? Check back in a few minutes or contact support.</small></p>
                        <div class="progress progress-striped active">
                            <div class="bar" style="width: 100%;"></div>
                        </div>
                    </div>
                </td>
            </tr>
        </tbody>
    </table>

</div>

<script src="https://ajax.googleapis.com/ajax/libs/jquery/1.8.2/jquery.min.js"></script>
<script src="js/bootstrap.min.js"></script>
<script src="js/jquery.tablesorter.min.js"></script>
<script>$(document).ready(function () {
    var auto_refresh,
        refreshDelay = 30 * 1000,
        errors = 0,
        errorsThreshold = 2,
        alert = $('<div class="alert alert-error fade">The status server is currently not responding. We\'ll continue trying, but the below information may not be updated.</div>'),
        progressBar = $('#update-progress'),
        progressBarActiveClasses = 'progress-striped active',
        progressBarIdleClasses = 'progress-success',
        tableBody = $('#ajaxdata'),
        updatedText = $('#lastupdated');

    setInterval(auto_refresh = function () {
        progressBar.removeClass(progressBarIdleClasses);
        progressBar.addClass(progressBarActiveClasses);
        tableBody.load('data.php', function (response, status, xhr) {
            if (status == 'error') {
                if (++errors >= errorsThreshold)
                    alert.prependTo($('body > .container').first()).addClass('in');
                return;
            } else {
                errors = 0;
                alert.alert('close');
            }

            tableBody.animate({opacity: .3},100,function() {
                tableBody.animate({opacity: 1},250);
                tableBody.html(response);

                var curTime = new Date(),
                    timeHours = curTime.getHours(),
                    timeMinutes = curTime.getMinutes(),
                    timeSeconds = curTime.getSeconds();
                if (timeHours < 10) timeHours = '0' + timeHours;
                if (timeMinutes < 10) timeMinutes = '0' + timeMinutes;
                if (timeSeconds < 10) timeSeconds = '0' + timeSeconds;

                var timeString = timeHours + ':' + timeMinutes + ':' + timeSeconds;

                updatedText.html(timeString);
            });
            progressBar.removeClass(progressBarActiveClasses);
            progressBar.addClass(progressBarIdleClasses);
        });
    }, refreshDelay);

    // trigger refresh without waiting
    auto_refresh();
});
</script>

</body>
</html>
