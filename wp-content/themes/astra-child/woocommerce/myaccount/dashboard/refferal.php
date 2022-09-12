<div class="dashboard-referral">
    <div class="mto-dashboard-container">
        <div class="dashboard-ref-img" style="background-image: url(<?php echo get_stylesheet_directory_uri(); ?>/assets/images/dashboard/dashboard-referral.jpg);">
        </div>
        <div class="dashboard-ref-content">
            <p class="dashboard-ref-title"><?php echo __('Refer a friend and recieve $20 each', 'panierquebecois'); ?></p>
            <p class="dashboard-ref-text"><?php echo __('Send the link below to your friends and receive $20 for each friend you refer! They will also get a $20 discount on their first order.', 'panierquebecois'); ?></p>
             <p><?php echo home_url(); ?>?pqc=<?php echo $user_id ?></p>
            <div class="dashboard-ref-btn">
                <a data-clipboard="<?php echo home_url(); ?>?pqc=<?php echo $user_id ?>" href="#";>
                    <img src="<?php echo get_stylesheet_directory_uri(); ?>/assets/images/dashboard/copy-icon.png" alt="Copy Icon" />
                    <span><?php echo __('Copy url', 'panierquebecois'); ?></span>
                </a>
            </div>
        </div>
    </div>
</div>
