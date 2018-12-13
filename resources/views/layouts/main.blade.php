<!doctype html>
<html lang="en" class="no-js">
<head>
  <meta charset="UTF-8" />
  <title>@yield('title')</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="stylesheet" href="css/style.css" />
  <link href='http://fonts.googleapis.com/css?family=Montserrat:400,700' rel='stylesheet' type='text/css'>
  <!-- remember, jQuery is completely optional -->
  <script type='text/javascript' src='jquery/dist/jquery.min.js'></script>
  <script type='text/javascript' src='js/jquery.particleground.js'></script>
</head>

<body>

@yield('content')

<script>
$(document).ready(function() {
    $('#particles').particleground({
        dotColor: '#5cbdaa',
        lineColor: '#5cbdaa'
    });
    var intro = document.getElementById('intro');
    intro.style.marginTop = - intro.offsetHeight / 2 + 'px';
});
</script>
@yield('footerjs')
</body>
</html>