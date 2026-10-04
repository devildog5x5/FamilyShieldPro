<?php
/** @var array<string, mixed> $page */
Layout::start((string) $page['title'], $user ?? null);
?>
<div class="wrap app-main guide">
  <h1><?= Http::e((string) $page['h1']) ?></h1>
  <p class="lede"><?= Http::e((string) $page['lede']) ?></p>
  <?php foreach ($page['sections'] as $section): ?>
    <h2><?= Http::e((string) $section['h2']) ?></h2>
    <?php foreach ($section['paragraphs'] ?? [] as $paragraph): ?>
      <p><?= Http::e((string) $paragraph) ?></p>
    <?php endforeach; ?>
    <?php if (!empty($section['list'])): ?>
      <ul class="list">
        <?php foreach ($section['list'] as $item): ?>
          <li><?= Http::e((string) $item) ?></li>
        <?php endforeach; ?>
      </ul>
    <?php endif; ?>
    <?php if (!empty($section['links'])): ?>
      <ul class="list">
        <?php foreach ($section['links'] as $link): ?>
          <li><a href="<?= Http::e((string) $link['href']) ?>" rel="noopener noreferrer" target="_blank"><?= Http::e((string) $link['label']) ?></a> — <?= Http::e((string) $link['note']) ?></li>
        <?php endforeach; ?>
      </ul>
    <?php endif; ?>
  <?php endforeach; ?>
  <div class="panel guide-next">
    <h2>Use it with your household</h2>
    <p>A new circle includes a 14-day trial, then Family monthly is $14.99 or Family yearly is $119.99. Paying does not make a request safe. You keep what you entered. We do not sell people’s information.</p>
    <p><a class="btn" href="/signup">Start a 14-day trial</a></p>
  </div>
  <h2>More guides</h2>
  <ul class="list">
    <?php foreach (Guides::all() as $other): ?>
      <?php if ($other['path'] === $page['path']) { continue; } ?>
      <li><a href="<?= Http::e((string) $other['path']) ?>"><?= Http::e((string) $other['h1']) ?></a></li>
    <?php endforeach; ?>
  </ul>
  <p><a href="/">Home</a> · <a href="/privacy">Privacy</a> · <a href="/terms">Terms &amp; Conditions</a></p>
</div>
<?php Layout::end($user ?? null);
