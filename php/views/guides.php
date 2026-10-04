<?php
/** @var list<array<string, mixed>> $pages */
Layout::start('Family guides · OurCircle', $user ?? null);
?>
<div class="wrap app-main guide">
  <h1>Guides for a family pause</h1>
  <p class="lede">Short pages about what OurCircle actually does: a household pause before money, gift cards, or crypto. They are guidance, not a guarantee, and never a stamp that a request is safe.</p>
  <div class="guide-index">
    <?php foreach ($pages as $page): ?>
      <a class="panel" href="<?= Http::e((string) $page['path']) ?>">
        <h2><?= Http::e((string) $page['h1']) ?></h2>
        <p><?= Http::e((string) $page['lede']) ?></p>
      </a>
    <?php endforeach; ?>
  </div>
  <p><a class="btn" href="/signup">Start a 14-day trial</a></p>
</div>
<?php Layout::end($user ?? null);
