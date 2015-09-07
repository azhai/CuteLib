<!DOCTYPE html>
<html lang="zh">
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width">
	<title><?= $title ?></title>
	<link rel="profile" href="http://gmpg.org/xfn/11">
	<link rel="pingback" href="/pingback/">
	<!--[if lt IE 9]>
	<script src="/assets/js/html5.js"></script>
	<![endif]-->
</head>

<body class="wp-content">
	<div id="page" class="hfeed site">
		<header id="masthead" class="site-header" role="banner">
			<a class="home-link" href="/" title="" rel="home">
				<h1 class="site-title"><?= $site['name'] ?></h1>
				<h2 class="site-description"><?= $site['description'] ?></h2>
			</a>

			<div id="navbar" class="navbar">
				<nav id="site-navigation" class="navigation main-navigation" role="navigation">
					<button class="menu-toggle"><?= _( 'Menu', 'twentythirteen' ); ?></button>
					<a class="screen-reader-text skip-link" href="#content" title="<?= _( 'Skip to content', 'twentythirteen' ); ?>">
						<?= _( 'Skip to content', 'twentythirteen' ); ?>
					</a>
					<!-- nav_menu -->
				</nav><!-- #site-navigation -->
			</div><!-- #navbar -->
		</header><!-- #masthead -->

		<div id="main" class="site-main">
		<?php $this->blockStart('content'); ?>
		<?php $this->blockEnd(); ?>
		</div><!-- #main -->

		<footer id="colophon" class="site-footer" role="contentinfo">
			<!-- sidebar -->

			<div class="site-info">
				<a href="" title="<?= _( 'Semantic Personal Publishing Platform', 'twentythirteen' ); ?>">
					<?= _( 'Proudly powered by %s', 'twentythirteen' ); ?>
				</a>
			</div><!-- .site-info -->
		</footer><!-- #colophon -->
	</div><!-- #page -->

</body>
</html>
