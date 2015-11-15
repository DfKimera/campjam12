<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
	<head>
		<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
		<title>Erro fatal!</title>

		<link rel="stylesheet" type="text/css" href="<?= APP_ANCHOR_PATH ?>assets/diesel.error.css" />

	</head>

	<body>


		<div id="content">

			<h1>Erro fatal!</h1>

			<p>Ocorreu o seguinte erro ao executar a ação:</p>

			<p class="important"><strong><?= $errorMessage ?></strong></p>

			<h2>Dados do erro</h2>
			<p><strong>Data:</strong> <?= $errorDate ?></p>
			<p><strong>Script:</strong> <?= $errorScript ?></p>
			<p><strong>Linha:</strong> <?= $errorLine ?></p>
			<h2>Stack de execu&ccedil;&atilde;o</h2>
			<p>
					<?= $runtimeStack ?>
					<br /><br />
			</p>

			<h2>Log de execu&ccedil;&atilde;o</h2>
			<p><?= $runtimeLog ?></p>

			<h2>Par&acirc;metros POST e GET</h2>
			<p><?= $runtimeParameters ?></p>

			<h2>Vari&aacute;veis de sess&atilde;o</h2>
			<p><?= $sessionVars ?></p>

			<h2>Cookies</h2>
			<p><?= $cookies ?></p>

			<h2>M&oacute;dulos carregados</h2>
			<p><?= $loadedModules ?></p>

			<h2>Buffer de sa&iacute;da</h2>
			<p><?= $outputBuffer ?></p>


			<h5>Powered by Diesel Framework 3.0 - LQDI Technologies - 2012</h5>

		</div>


	</body>
</html>