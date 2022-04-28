jQuery(document).ready(function ($) {    
    pq_hash = window.location.hash;
    
    if ( pq_hash.indexOf('?') !== -1 ) {
        pq_url_parameters = pq_hash.slice( pq_hash.indexOf('?'), pq_hash.length );
        pq_hash_only = pq_hash.slice( pq_hash.indexOf('#'), pq_hash.indexOf('?') );
        
        pq_fixed_url = pq_url_parameters + pq_hash_only;
        
        window.location = pq_fixed_url;
    }
});