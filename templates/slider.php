<?php
// Security measure to prevent direct access to the plugin file.
if ( ! defined( 'ABSPATH' ) ) {
    die( 'Sorry, you are not allowed to access this page directly.' );
}

    $atts = shortcode_atts(array(
        'text_color'       => 'rgb(9, 38, 138)',
        'outerglow_color'  => 'rgb(220, 253, 178)',
    ), $atts);
    global $bleuh_slider_atts;
    $bleuh_slider_atts = $atts;

    $sid = md5("bleuh-".time());

?>
<div class="swiper" id="swiper-s<?php echo $sid; ?>">
    <div class="swiper-wrapper">
        <?php echo do_shortcode($content); ?>
    </div>

    <a href="#" class="swiper-prev"> &lt; </a>
    <a href="#" class="swiper-next"> &gt; </a>
    <div class="swiper-pagination-wrapper">
        <div class="swiper-pagination"></div>
    </div>
</div>

<script type="text/javascript">
    jQuery(document).ready(function ($) {


        const swiper = new Swiper('#swiper-s<?php echo $sid; ?>', {
            // auto init the instance
            init: true,
            autoplay: true,
            disableOnInteraction: true,
            pauseOnMouseEnter: true,

            // 'horizontal' or 'vertical'
            direction: 'horizontal',
            pagination: {
                el: '.swiper-pagination',
            },
            navigation: {
                prevEl: '.swiper-prev',
                nextEl: '.swiper-next',
            },
            loop: true,
            /*
            // target element to listen touch events on.
            touchEventsTarget: 'container',

            // initial slide index
            initialSlide: 0,

            // Inject text styles to the shadow DOM. Only for usage with Swiper Element
            injectStyles: ''

            // Inject styles <link>s to the shadow DOM. Only for usage with Swiper Element
            injectStylesUrls: '',

            // Number of next and previous slides to preload. Only applicable if using lazy loading.
            lazyPreloadPrevNext: 0,

            // CSS class name of lazy preloader
            lazyPreloaderClass  string  'swiper-lazy-preloader',

            // animation speed
            speed: 300,

            // whether to use modern CSS <a href="https://www.jqueryscript.net/tags.php?/Scroll/">Scroll</a> Snap API.
            cssMode: false,

            // auto up<a href="https://www.jqueryscript.net/time-clock/">date</a> on window resize
            updateOnWindowResize: true,

            // Overrides
            width: null,
            height: null,

            // allow to change slides by swiping or navigation/pagination buttons during transition
            preventInteractionOnTransition: false,

            // for ssr
            userAgent: null,
            url: null,

            // To support iOS's swipe-to-go-back gesture (when being used in-app).
            edgeSwipeDetection: false,
            edgeSwipeThreshold: 20,

            // Free mode
            // If true then slides will not have fixed positions
            freeMode: false,
            freeModeMomentum: true,
            freeModeMomentumRatio: 1,
            freeModeMomentumBounce: true,
            freeModeMomentumBounceRatio: 1,
            freeModeMomentumVelocityRatio: 1,
            freeModeSticky: false,
            freeModeMinimumVelocity: 0.02,

            // Autoheight
            autoHeight: false,

            // Set wrapper width
            setWrapperSize: false,

            // Virtual Translate
            virtualTranslate: false,

            // slide' or 'fade' or 'cube' or 'coverflow' or 'flip'
            effect: 'slide',

            // Breakpoints
            breakpoints: {
                // when window width is >= 320px
                320: {
                  slidesPerView: 2,
                  spaceBetween: 20
                },
                // when window width is >= 480px
                480: {
                  slidesPerView: 3,
                  spaceBetween: 30
                },
                // when window width is >= 640px
                640: {
                  slidesPerView: 4,
                  spaceBetween: 40
                }
            }
            breakpoints: undefined,

            // Slides grid
            spaceBetween: 0,
            slidesPerView: 1,
            slidesPerColumn: 1,
            slidesPerColumnFill: 'column',
            slidesPerGroup: 1,
            slidesPerGroupSkip: 0,
            centeredSlides: false,
            centeredSlidesBounds: false,
            slidesOffsetBefore: 0,

            // in px
            slidesOffsetAfter: 0,
            // in px
            normalizeSlideIndex: true,
            centerInsufficientSlides: false,

            // Disable swiper and hide navigation when container not overflow
            watchOverflow: false,

            // Round length
            roundLengths: false,

            // Options for touch events
            touchRatio: 1,
            touchAngle: 45,
            simulateTouch: true,
            shortSwipes: true,
            longSwipes: true,
            longSwipesRatio: 0.5,
            longSwipesMs: 300,
            followFinger: true,
            allowTouchMove: true,
            threshold: 0,
            touchMoveStopPropagation: false,
            touchStartPreventDefault: true,
            touchStartForcePreventDefault: false,
            touchReleaseOnEdges: false,

            // Unique Navigation Elements
            uniqueNavElements: true,

            // Resistance
            resistance: true,
            resistanceRatio: 0.85,

            // Use ResizeObserver (if supported by browser) on swiper container to detect container resize (instead of watching for window resize)
            resizeObserver: false,

            // Progress
            watchSlidesProgress: false,
            watchSlidesVisibility: false,

            // Cursor
            grabCursor: false,

            // Clicks
            preventClicks: true,
            preventClicksPropagation: true,
            slideToClickedSlide: false,

            // When enabled, will swipe slides only forward (one-way) regardless of swipe direction
            oneWayMovement: true,

            // Images
            preloadImages: true,
            updateOnImagesReady: true,

            // loop
            loop: false,
            loopAdditionalSlides: 0,
            loopedSlides: null,
            loopPreventsSliding: true,
            loopFillGroupWithBlank: false,
            loopPreventsSlide: true,

            // Swiping/no swiping
            allowSlidePrev: true,
            allowSlideNext: true,
            swipeHandler: null,

            // '.swipe-handler',
            noSwiping: true,
            noSwipingClass: 'swiper-no-swiping',
            noSwipingSelector: null,

            // Passive Listeners
            passiveListeners: true,

            // Default classes
            containerModifierClass: 'swiper-container-',
            slideClass: 'swiper-slide',
            slideBlankClass: 'swiper-slide-invisible-blank',
            slideActiveClass: 'swiper-slide-active',
            slideDuplicateActiveClass: 'swiper-slide-duplicate-active',
            slideVisibleClass: 'swiper-slide-visible',
            slideDuplicateClass: 'swiper-slide-duplicate',
            slideNextClass: 'swiper-slide-next',
            slideDuplicateNextClass: 'swiper-slide-duplicate-next',
            slidePrevClass: 'swiper-slide-prev',
            slideDuplicatePrevClass: 'swiper-slide-duplicate-prev',
            wrapperClass: 'swiper-wrapper',

            // Callbacks
            runCallbacksOnInit: true
            */
        });

        jQuery(document).on('mouseenter', ".swiper-slide.swiper-slide-active", function () {
            swiper.autoplay = false;
            swiper.update();
        });

        /*
        jQuery(document).on('mouseleave', ".swiper-slide.swiper-slide-active", function() {
            swiper.autoplay = true;
            swiper.update();
        });
        */
    });
</script>

