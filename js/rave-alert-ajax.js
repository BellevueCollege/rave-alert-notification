/*
* Pull data from xml feed
*
*/


jQuery( document ).ready( function( $ ) {
    
    var counter = 0;

    (function callAjax() {

        counter++;
        console.log('Ajax called #' + counter + ', Timeout is 1 minute');

        $.ajax({
            method: 'GET',
            url: rest_php_variables['rest_url'],
        }).done(function (identifier) {
            console.log('identifier: ' + identifier);

            // verify data (identifier) returned is a string 
            if ( typeof identifier == 'string' ) {
                var more_info_message = rest_php_variables['more_info_message'];

                //check if #ravealertheader does not exist in <body>
                if ($('#ravealertheader').length == 0) {
                    $('body').prepend('<div id="ravealertheader" class="container alert"><div class="row"><div class="col-sm-2"><span class="glyphicon glyphicon-warning-sign" aria-hidden="true"></span></div><div class="col-sm-10"><div id="ravealertmessage"><h2 id="ravealertevent">' + 'Loading Alert...' + '</h2><p>' + 'Loading headline...' + ' ' + more_info_message + '</p></div></div></div></div>');
                }
                
                $.ajax({
                    method: 'GET',
                    url: rest_php_variables['rest_url'] + identifier,
                }).done(function (data) {
                    console.log('data: ' + JSON.stringify(data));

                    var output = '';

                    // verify data returned is JSON 
                    if (typeof data == 'object') {

                        // Set variables
                        var new_event = data.info['event'];
                        var new_headline = data.info['headline'];
                        var new_class = data.info['class'];
                        
                        output += '<div id="ravealertheader" class="container ' + new_class + '"><div class="row"><div class="col-sm-2"><span class="glyphicon glyphicon-warning-sign" aria-hidden="true"></span></div><div class="col-sm-10"><div id="ravealertmessage"><h2 id="ravealertevent">' + new_event + '</h2><p>' + new_headline + ' ' + more_info_message + '</p></div></div></div></div>';

                    } else { // if non - JSON data is returned
                        console.log('Error rave-alert-ajax: Non-JSON data returned.');
                    }

                    //Replace #ravealertheader with new output
                    $('#ravealertheader').replaceWith( output );
                }).fail(function (error) {
                    console.log('Error calling REST API: ');
                    console.log(error);
                });
            } else { // if string is not returned
                console.log('Error rave-alert-ajax: Non-string data returned.');

                //check if an alert exists and remove it
                if ($('#ravealertheader').length == 1){
                    $('#ravealertheader').remove();
                }
            }

        }).fail(function(error){
            console.log('Error calling REST API: ' );
            console.log(error);
        }).always(function() { 
            setTimeout(callAjax, 60000 * 1); 
        });
    })();


});