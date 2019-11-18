<!doctype html>

<html class="no-js" lang="">

<head>

	<title>Work weather</title>
	<meta charset="utf-8">
	<meta name="robots" content="noindex">

	<style type="text/css">
		body  {
			text-align: center;
		}
		table {
				display: inline-block;
		}
		td {
				text-align: left;
				padding: 2px 5px;
		}
		.color {
			font-weight: bold;
			text-align: center;
		}
		table.forecast {
			margin-top: 1em;
			border-spacing: 0;
			border-collapse: collapse;
		}
		table.forecast th {
			background-color: #eee;
			border: solid thin #000;
			margin: 0;
		}
		table.forecast td {
			background-color: #fafafa;
			height: 2em;
			text-align: center;
			padding: 10px;
			margin: 0;
			border: solid thin #000;
		}
		table.forecast td.week {
			background-color: #eee;
		}
		table.forecast th:first-child {
			background: transparent;
			border-left: none;
			border-top: none;
		}
		table.forecast .green {
			background-color: #0f0;
			color: #000;
		}
		table.forecast .orange {
			background-color: #fa0;
			color: #000;
		}
		table.forecast .red {
			background-color: #f00;
			color: #fff;
		}
		table.forecast .black {
			background-color: #000;
			color: #fff;
		}
		table.forecast .blue {
			background-color: #00f;
			color: #fff;
		}
		table.forecast td:nth-child(7),
		table.forecast td:last-child,
		table.forecast th:nth-child(7),
		table.forecast th:last-child {
			/*opacity: 0.5;*/
			background-image: url('background-noise.png');
		}
	</style>

</head><body>

<h1>Météo</h1>

<p><img src="weather.php?mode=large" /></p>

<table>
	<tr><td class="color"><span style="color:#0f0">Vert</span></td><td>Charge faible, réponse sous 24 heures. Nouveaux projets possibles.</td>
	<tr><td class="color"><span style="color:#fa0">Orange</span></td><td>Charge moyennement importante, réponse sous 24/48 heures.</td>
	<tr><td class="color"><span style="color:#f00">Rouge</span></td><td>Charge importante, réponse sous 3 jours. Nouveaux projets reportés.</td>
	<tr><td class="color"><span style="color:#000">Noir</span></td><td>Charge très importante, délais dépassés, réponse sous 3 jours. Nouveaux projets suspendus.</td>
	<tr><td class="color"><span style="color:#00f">Bleu</span></td><td>Absence ou bénévolat, réponse sous 3/7 jours.</td>
</table>

<h2>Prévisions à 60 jours</h2>

<?php require_once('weather_table.php'); ?>

</body></html>
