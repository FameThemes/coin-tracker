jQuery( document ).ready( function( $ ){
    $( ".datepicker" ).datepicker({
        altFormat: "yy-mm-dd",
        dateFormat: "yy-mm-dd",
        altField: "#coin_tracker_date_add"
    });
} );