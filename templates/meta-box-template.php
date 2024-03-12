<?php
$yes_percentage = $voteRatio['ratio_true'];
$no_percentage = $voteRatio['ratio_false'];

if (!$yes_percentage && !$no_percentage) :
    ?>
<p><?php _e("Article has not been rated yet.", "article-voting"); ?></p>
<?php else: ?>
<div class="percentage-container">
    <div class="percentage-line percentage-line--yes" style="width:<?= $yes_percentage; ?>%"></div>
    <div class="percentage-line percentage-line--no" style="width:<?= $no_percentage; ?>%"></div>
</div>

<p><?php _e("Article was helpful", "article-voting"); ?> - <?= $yes_percentage; ?>%</p>
<p><?php _e("Article was not helpful", "article-voting"); ?> - <?= $no_percentage; ?>%</p>
<?php endif; ?>