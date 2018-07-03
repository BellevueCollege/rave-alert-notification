/*
* Pull data from xml feed
*
*/


jQuery( document ).ready( function( $ ) {
    
    var counter = 0;

    //Call Ajax after 1 minute
    //var ajaxTimeout = setTimeout(callAjax, 60000 * 1);
    //clearTimeout(ajaxTimeout);

    callAjax();
    function callAjax() {
        counter++;
        console.log('Ajax called #' + counter + ', Timeout is 1 minute');

        $.ajax({
            method: 'GET',
            url: rest_php_variables['rest_url'],
        }).done(function(data){
            console.log(data);

            var output = '';

            //console.log(typeof data);

            // verify data returned is JSON 
            if ( typeof data == 'string' ) {
                    /* GET ALERT! */
            } else { // if non - JSON data is returned
                console.log('false');
            }

            // Output to DOM
            $('#ravealertheader > .row:first').replaceWith( output );
            //console.log('output:' + output);  
        }).fail(function(error){
            console.log('Error calling REST API: ' );
            console.log(error);
        }).always(function() { 
            setTimeout(callAjax, 60000 * 1); 
        });




        $.ajax({
            method: 'GET',
            url: rest_php_variables['rest_url'],
        }).done(function (data) {
            console.log(data);

            var output = '';

            // verify data returned is JSON 
            if (typeof data == 'object') {

                // Set variables for ease of use
                var new_id = data.identifier;
                var new_event = data.info['event'];
                var new_headline = data.info['headline'];
                var new_data = data;
                var more_info_message = rest_php_variables['more_info_message'];
                output += "<div class='row'><div class='col-sm-2'><span class='glyphicon glyphicon-warning-sign' aria-hidden='true'></span></div><div class='col-sm-10'><div id='ravealertmessage'><h2 id='ravealertevent'> AJAX" + new_event + "</h2><p>" + new_headline + " " + more_info_message + "</p></div></div></div>";

                //console.log('new id: ' + new_id);
                //console.log('new event: ' + new_event);
                //console.log('new data: ' + JSON.stringify(new_data));

            } else { // if non - JSON data is returned
                console.log('ERROR rave-alert-ajax: Non-JSON data returned.');
            }

            // Output to DOM
            $('#ravealertheader > .row:first').replaceWith(output);
            //console.log('output:' + output);  
        }).fail(function (error) {
            console.log('Error calling REST API: ');
            console.log(error);
        }).always(function () {
            setTimeout(callAjax, 60000 * 1);
        });
    }

});