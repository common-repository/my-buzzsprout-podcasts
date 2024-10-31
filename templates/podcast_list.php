<?php

function mbpl_my_buzzsprout_get_subscription_id($feed_uri = false) {
    if (!preg_match_all('|^https?://(feeds\.)?buzzsprout\.com/([0-9]+)\.rss$|i', $feed_uri, $matches))
        return false;
    return isset($matches[2][0]) ? $matches[2][0] : false;
}

function mbpl_my_buzzsprout_getExcerpt($str, $startPos = 0, $maxLength = 100) {
    if (strlen($str) > $maxLength) {
        $excerpt = substr($str, $startPos, $maxLength - 3);
        $lastSpace = strrpos($excerpt, ' ');
        $excerpt = substr($excerpt, 0, $lastSpace);
        $excerpt .= '...';
    } else {
        $excerpt = $str;
    }

    return $excerpt;
}
function mbpl_my_buzzsprout_enqueue_scripts($podcasat_id,$subscription_id) {
    wp_register_script('buzzsprout-script'.$podcasat_id, 'https://www.buzzsprout.com/'.$subscription_id.'/'.$podcasat_id.'.js?container_id=buzzsprout-player-'.$podcasat_id.'&player=small');
    return wp_enqueue_script('buzzsprout-script'.$podcasat_id);
}

$args = array(
    'post_type' => 'podcast',
    'orderby' => 'ID',
    'post_status' => 'publish',
    'order' => 'DESC',
    'posts_per_page' => -1 // this will retrive all the post that is published 
);
?>

<div class="container-podcast">
    <?php
    $result = new WP_Query($args);
    if ($result->have_posts()) :
        while ($result->have_posts()) : $result->the_post();
            ?>
            <div class="post-block">
                <div class="post-title"><?php the_title(sprintf('<h2 class="entry-title"><a href="%s" rel="bookmark">', esc_url(get_permalink())), '</a></h2>'); ?></div>
                <div class="row">
                    <div class="col">
                        <?php
                        $episod_id = explode("-", get_post_meta(get_the_ID(), 'podcast_embed_id', true));
                        $episod_id = $episod_id[1];
                        $subscription_id = mbpl_my_buzzsprout_get_subscription_id("https://feeds.buzzsprout.com/1716785.rss");
                        $parsed_html = '<div id="buzzsprout-player-'.$episod_id.'"></div>'.mbpl_my_buzzsprout_enqueue_scripts($episod_id, $subscription_id);
                         echo $parsed_html;
                        ?>
                    </div>
                    <div class="col">
                        <div style="padding: 0 15px;">
                            <?php
                            echo mbpl_my_buzzsprout_getExcerpt(get_post_meta(get_the_ID(), 'podcast_discription_text', true), 0, 250);
                            echo '<a class="btn" href="' . esc_url(get_permalink()) . '" rel="bookmark">View More</a>';
                            ?>
                        </div>
                    </div>
                </div>
            </div>
            <?php
        endwhile;
    endif;
    wp_reset_postdata();
    ?>
</div>
