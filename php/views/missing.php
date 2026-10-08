<?php
Layout::start('This OurCircle address does not have a page', $user ?? null);
?>
<div class="wrap app-main missing-page">
  <h1>That page is not here</h1>
  <p>The link may be old, or the address may be mistyped. The menu on this page opens every activity: Home, Check, Circle, Trusted list, Report, Plans, and Account.</p>
  <p><a class="btn" href="/">Go to the home page</a></p>
  <p><?php echo Layout::supportNote(); ?></p>
</div>
<?php Layout::end($user ?? null);
