<?php
// Security measure to prevent direct access to the plugin file.
if ( ! defined( 'ABSPATH' ) ) {
    die( 'Sorry, you are not allowed to access this page directly.' );
}

        global $rwi;
        global $bleuh_rw_atts;

        $atts = shortcode_atts(array(
            'bg_color'       => 'rgb(9, 38, 138)',
        ), $atts);
        $bleuh_rw_atts = $atts;
        $rwi = 9;
        $sid = md5("bleuh-".time());

?>
<div class="rotation-widget" id="fader-s<?php echo $sid; ?>" style="background:<?php echo $bleuh_rw_atts['bg_color']; ?>;">
    <div class="rw-wrapper">
        <?php echo do_shortcode($content); ?>
    </div>
</div>

<script type="text/javascript">
    jQuery(document).ready(function ($) {
        $('#fader-s<?php echo $sid; ?> .fader-slide').first().css('z-index', 3).show();
        $('#fader-s<?php echo $sid; ?> .fader-slide').last().addClass('active');
        setInterval(function(){
            $active = $('#fader-s<?php echo $sid; ?> .fader-slide.active').first();
            $next = ($('#fader-s<?php echo $sid; ?> .fader-slide.active').first().next().length > 0) ? $('#fader-s<?php echo $sid; ?> .fader-slide.active').first().next() : $('#fader-s<?php echo $sid; ?> .fader-slide:first');
            $next.first().css('z-index',2);
            $active.fadeOut(300,function(){
                $active.first().css('z-index',1).show().removeClass('active');
                $next.first().css('z-index',3).addClass('active');
            });
        }, 1500);
    });
</script>
