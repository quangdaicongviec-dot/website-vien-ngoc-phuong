<?php
require __DIR__ . '/includes/init.php';

$article = find_article((string) ($_GET['bv'] ?? ''));
if (!$article) {
    http_response_code(404);
    include ROOT . '/404.php';
    exit;
}

$a          = $article;
$others     = array_slice(array_values(array_filter(all_articles(), fn($x) => $x['slug'] !== $a['slug'])), 0, 3);
$page_title = $a['title'];
$page_desc  = $a['excerpt'];
$page_image = $a['image'];
$page_type  = 'article';
$nav_active = 'blog';
$body_class = 'page-article';
$canonical  = absolute_url(article_url($a));
$head_extra = '<script type="application/ld+json">' . json_encode([
    '@context'      => 'https://schema.org',
    '@type'         => 'Article',
    'headline'      => $a['title'],
    'description'   => $a['excerpt'],
    'image'         => absolute_url(img_url($a['image'])),
    'datePublished' => $a['date'],
    'author'        => ['@type' => 'Organization', 'name' => $a['author'] ?? cfg('site_name')],
    'publisher'     => ['@type' => 'Organization', 'name' => cfg('site_name'), 'logo' => ['@type' => 'ImageObject', 'url' => absolute_url(img_url('logo.png'))]],
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG) . '</script>';

include ROOT . '/includes/header.php';
?>

<article class="article">
  <header class="article__head">
    <div class="container container--narrow">
      <?= render_breadcrumbs([['label' => 'Cẩm nang', 'url' => url('cam-nang')], ['label' => $a['title']]]) ?>
      <h1 class="article__title"><?= e($a['title']) ?></h1>
      <p class="article__meta"><?= icon('calendar') ?><time datetime="<?= e($a['date']) ?>"><?= e(format_date($a['date'])) ?></time><span>· <?= e($a['author'] ?? cfg('site_name')) ?></span></p>
    </div>
  </header>
  <div class="container container--narrow">
    <figure class="article__cover">
      <img src="<?= e(img_url($a['image'])) ?>" alt="" width="800" height="800">
    </figure>
    <div class="prose article__body">
      <p class="lead"><?= e($a['excerpt']) ?></p>
      <?= $a['content'] ?>
    </div>

    <aside class="cta-band cta-band--card" aria-label="Tư vấn">
      <div>
        <h2 class="section-title section-title--sm">Cần tư vấn thêm?</h2>
        <p>Liên hệ <?= e(cfg('site_name')) ?> để được tư vấn chọn ngọc, đo ni tay và xem video sản phẩm thật.</p>
      </div>
      <div class="cta-band__actions">
        <?php if (cfg('zalo')): ?><a class="btn btn--primary" href="<?= e(zalo_href()) ?>" target="_blank" rel="noopener"><?= icon('chat') ?>Chat Zalo</a><?php endif; ?>
        <a class="btn btn--outline" href="<?= e(url('san-pham')) ?>">Xem sản phẩm</a>
      </div>
    </aside>
  </div>
</article>

<?php if ($others): ?>
<section class="section section--muted" aria-labelledby="more-title">
  <div class="container">
    <h2 id="more-title" class="section-title section-title--sm">Bài viết khác</h2>
    <div class="post-grid post-grid--compact">
      <?php foreach ($others as $a) include ROOT . '/includes/article-card.php'; ?>
    </div>
  </div>
</section>
<?php endif; ?>

<?php include ROOT . '/includes/footer.php'; ?>
