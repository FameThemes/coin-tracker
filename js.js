jQuery( document ).ready( function($){

// jQuery cross domain ajax




    $.ajax({
        type:"GET",
        //async:false,
        url: coin_tracker.ajax_url,
        dataType: 'html',
        data: {
            action: 'coin_tracker_load_content',
            url:'https://bitconnect.co/bitcoin-price'
        },
        success: function( html ){
            var $html = $( html );
            var btc = $( '.header-info-col a[title="Bitcoin price"] span span', $html ).html();
            btc = btc.replace( '=', '' );
            btc = btc.trim();
            btc = btc.replace( '$', '' );
            $( '.coin_btc_to_usd' ).html( btc );

            var bcc = $( '.header-info-col a[title="Bitconnect price"] span span', $html ).html();
            bcc = bcc.replace( '=', '' );
            bcc = bcc.trim();
            bcc = bcc.replace( '$', '' );
            $( '.coin_bcc_to_usd' ).html( bcc );



        }
    });



} );