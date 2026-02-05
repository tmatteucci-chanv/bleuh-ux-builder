<?php

// Security measure to prevent direct access to the plugin file.
if ( ! defined( 'ABSPATH' ) ) {
    die( 'Sorry, you are not allowed to access this page directly.' );
}

function bleuh_age_gate_popup() {
echo '
    <div class="age-gate__wrapper" style="display:none;">
        <div class="age-gate" role="dialog" aria-modal="true" aria-label="">
            <form method="post" class="age-gate__form">
                <div class="age-gate__heading"><img
                            src="/wp-content/uploads/2022/11/Logo-pour-fond-bleu-1.png"
                            alt="Bleuh" class="age-gate__heading-title age-gate__heading-title--logo"></div>
                <h2 class="age-gate__headline"> '.icl_t('bleuh', 'caption_ag_title').'</h2>
                <p class="age-gate__subheadline"> '.icl_t('bleuh', 'caption_ag_blurb').'</p>
                <div class="age-gate__fields"><p class="age-gate__challenge"></p>
                    <div class="age-gate__buttons">
                        <button class="age-gate__submit age-gate__submit--no" data-submit="no" value="0"
                                name="age_gate[confirm]" type="submit">'.icl_t('bleuh', 'caption_ag_quit').'
                        </button>
                        <button type="submit" class="age-gate__submit age-gate__submit--yes" data-submit="yes" value="1"
                                name="age_gate[confirm]">'.icl_t('bleuh', 'caption_ag_enter').'
                        </button>
                    </div>
                </div>
                <div class="age-gate__errors" style="display: none;">'.icl_t('bleuh', 'caption_ag_error').'</div>
            </form>
        </div>
    </div>';
}

add_action('wp_body_open', 'bleuh_age_gate_popup');