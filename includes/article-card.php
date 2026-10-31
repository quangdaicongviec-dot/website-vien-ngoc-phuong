<?php /** Thẻ bài viết — biến có sẵn: $a */ ?>
<article class="post-card">
  <a class="post-card__media" href="<?= e(article_url($a)) ?>" tabindex="-1" aria-hidden="true">
    <img src="<?= e(img_url($a['image'])) ?>" alt="" width="800" height="800" loading="lazy" decoding="async">
  </a>
  <div class="post-card__body">
    <p class="post-card__meta"><?= icon('calendar') ?><time datetime="<?= e($a['date']) ?>"><?= e(format_date($a['date'])) ?></time></p>
    <h3 class="post-card__title"><a href="<?= e(article_url($a)) ?>"><?= e($a['title']) ?></a></h3>
    <p class="post-card__excerpt"><?= e($a['excerpt']) ?></p>
    <a class="link-more" href="<?= e(article_url($a)) ?>" tabindex="-1" aria-hidden="true">Đọc tiếp<?= icon('arrow') ?></a>
  </div>
</article>
