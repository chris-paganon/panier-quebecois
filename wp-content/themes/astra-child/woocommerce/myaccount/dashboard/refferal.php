<div class="mto-dashboard-container">
    <div><!--IMAGE--></div>
    <div>
        <p><?php echo __('Refer a friend and recieve $20 each', 'panierquebecois'); ?></p>
        <p><?php echo __('Send the link below to your friends and recieve $20 for each refferred firend! They will also get a $20 discount on their first order.', 'panierquebecois'); ?></p>
        <a href="<?php echo home_url(); ?>?pqc=<?php echo $user_id ?>";><?php echo home_url(); ?>?pqc=<?php echo $user_id ?></a>
        <a data-clipboard="<?php echo home_url(); ?>?pqc=<?php echo $user_id ?>" href="#";><?php echo __('Copy url', 'panierquebecois'); ?></a>
        
    </div>
</div>
