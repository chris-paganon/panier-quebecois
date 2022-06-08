(function ($) {
    $(document).ready( function($) {

        console.log('LOADED PQ-ADMIN'); 

        function taxonomy_media_upload(button_class) {
            console.log('click taxonomy_media_button');
            var custom_media = true,
            original_attachment = wp.media.editor.send.attachment;
            $('body').on('click', button_class, function(e) {
                var button_id = '#'+$(this).attr('id');
                var send_attachment = wp.media.editor.send.attachment;
                var button = $(button_id);
                custom_media = true;
                wp.media.editor.send.attachment = function(props, attachment){
                    if ( custom_media ) {
                        $('#image_id').val(attachment.id);
                        $('#image_wrapper').html('<img class="custom_media_image" src="" style="margin:0;padding:0;max-height:100px;float:none;" />');
                        $('#image_wrapper .custom_media_image').attr('src',attachment.url).css('display','block');
                    } else {
                        return original_attachment.apply( button_id, [props, attachment] );
                    }
                }
                wp.media.editor.open(button);
                return false;
            });
        }
        taxonomy_media_upload('.taxonomy_media_button.button'); 
        $('body').on('click','.taxonomy_media_remove',function(){
            console.log('click taxonomy_media_remove');
            $('#image_id').val('');
            $('#image_wrapper').html('<img class="custom_media_image" src="" style="margin:0;padding:0;max-height:100px;float:none;" />');
        });

        $(document).ajaxComplete(function(event, xhr, settings) {
            var queryStringArr = settings.data.split('&');
            if( $.inArray('action=add-tag', queryStringArr) !== -1 ){
                var xml = xhr.responseXML;
                $response = $(xml).find('term_id').text();
                if($response!=""){
                    $('#image_wrapper').html('');
                }
            }
        });
    });
})(jQuery);