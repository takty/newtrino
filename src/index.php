<?php include('./topic/recent.php'); ?>
<?php $PAGE_CLASS = 'front-page';  include('./part/header.php'); ?>
		<section>
			<h2>About</h2>
			<p>This is a sample website of the Newtrino.</p>
		</section>
		<section class="recent">
			<h2>Blog</h2>
			<div class="neat-width">
				<ul class="nt-recent-list"><?php the_recent(6, '', 7); ?></ul>
			</div>
			<nav class="bar">
				<a href="topic/" class="btn">Show More...</a>
			</nav>
		</section>
	</main><!-- site-main -->
	<footer class="site-footer">
	</footer>
</div><!-- site -->
</body>
</html>
