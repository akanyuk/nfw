<html style="text-align:center"><head><title>Ошибка</title>
  <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<style>
<!--
BODY { MARGIN: 1em 20em; font: 9pt Verdana, Arial, Helvetica, sans-serif; }
#errorbox { BORDER: 2px solid #a20; }
H2 {MARGIN: 0; COLOR: #FFFFFF; BACKGROUND-COLOR: #a20; FONT-SIZE: 1.2em; PADDING: 4px; }
#errorbox DIV { PADDING: 6px 5px; BACKGROUND-COLOR: #eee; }
HR { padding: 0.2em 0; margin: 0; border: none; border-top: 1px solid #777; }
A { text-decoration: none; color: #106; }
A:hover { text-decoration: none; color: #00c; }
-->
</style>
</head>

<body>
<div id="errorbox">
   <h2>Ошибка</h2>
   <div>
		<?php echo htmlspecialchars($message); ?>
		<hr />
		<small><a href="javascript: history.go(-1)">Вернуться назад.</a></small>
	</div>
</div>
</body>
</html>