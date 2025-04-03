
<!-- Module -->
<div class="row">
	<div class="col s12">
		<h3>PHP Info</h3>
		<?php
		// Start output buffering
		ob_start();

		// Send phpinfo content
		phpinfo();

		// Get phpinfo content
		$html = ob_get_contents();

		// Flush the output buffer
		ob_end_clean();

		// Remove auth data
		// Taken from: https://www.php.net/manual/en/function.phpinfo.php#116715
		if (isset($_SERVER['PHP_AUTH_USER']))
			$html = str_replace($_SERVER['PHP_AUTH_USER'], '[ protected ]', $html);
		if (isset($_SERVER['PHP_AUTH_PW']))
			$html = str_replace($_SERVER['PHP_AUTH_PW'], '[ protected ]', $html);

		// Filter HTML content
		// Taken from: https://www.php.net/manual/en/function.phpinfo.php#77705
		// And: https://www.php.net/manual/en/function.phpinfo.php#84000
		// preg_match('%<style type="text/css">(.*?)</style>.*?(<body>.*</body>)%s', $html, $matches);
		preg_match('%<style type="text/css">(.*?)</style>.*?<body>(.*?)</body>%s', $html, $matches);

		# $matches [1]; # Style information
		# $matches [2]; # Body information
		
		// Output filtered HTML content
		echo "<div class=\"phpinfodisplay\">\n<style type=\"text/css\">\n",
					join(
						"\n",
						array_map(
							function ($i) {
								return ".phpinfodisplay " . preg_replace( "/,/", ",.phpinfodisplay ", $i );
							},
							// preg_split('/\n/', $matches[1])
							preg_split('/\n/', trim(preg_replace("/\nbody/", "\n", $matches[1])))
						)
					),
					"\n.phpinfodisplay table tr td.e { color: white; }\n", # minor css fix
					"</style>\n",
					$matches[2],
					"\n</div>\n";
		?>
	</div>
</div>