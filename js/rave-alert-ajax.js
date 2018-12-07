/*
* Pull data from xml feed
*
*/


jQuery( document ).ready( function( $ ) {

    (function callAjax() {

        $.ajax({
            method: 'GET',
            url: rest_php_variables['rest_url'],
        }).done(function (identifier) {

            // If there is an Alert via CAP XML
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

                    var output = '';

                    // verify data returned is JSON 
                    if (typeof data == 'object') {

                        // Set variables
                        var new_event = data.info['event'];
                        var new_headline = data.info['headline'];
                        var new_class = data.info['class'];
                        
                        output += '<div id="ravealertheader" class="container ' + new_class + '"><div class="row"><div class="col-sm-2"><span class="glyphicon glyphicon-warning-sign" aria-hidden="true"></span></div><div class="col-sm-10"><div id="ravealertmessage"><h2 id="ravealertevent">' + new_event + '</h2><p>' + new_headline + ' ' + more_info_message + '</p></div></div></div></div>';

                    }

                    //Replace #ravealertheader with new output
                    $('#ravealertheader').replaceWith( output );
                }).fail(function (error) {
                    console.log('Error calling RAVE REST API: ');
                    console.log(error);
                });

            } else { // if string is not returned
                
                //check if an alert exists and remove it
                if ($('#ravealertheader').length == 1){
                    $('#ravealertheader').remove();
                }
            }

        }).fail(function(error){
            console.log('Error calling RAVE REST API: ' );
            console.log(error);
        }).always(function() { 
            //Always run even if REST API fails

            //If there is an Open Message Alert (No CAP XML Alert)
            var more_info_message = rest_php_variables['more_info_message'];
            var open_message_desc = rest_php_variables['open_message_desc'];
            var open_message_class = rest_php_variables['open_message_class'];

            var open_output = '';

            if (open_message_desc != '' && open_message_class != '') {
                open_output += '<div id="ravealertheader" class="container ' + open_message_class + ' open-msg"><div class="row"><div class="col-sm-2"><span class="glyphicon glyphicon-warning-sign" aria-hidden="true"></span></div><div class="col-sm-10"><div id="ravealertmessage"><p>' + open_message_desc + ' ' + more_info_message + '</p></div></div></div></div>';
                
                //check if #ravealertheader does not exist in <body>
                if ($('#ravealertheader').length == 0) {
                    $('body').prepend(open_output);
                } else { 
                    //Replace #ravealertheader with new output
                    $('#ravealertheader').replaceWith( open_output );
                }
            } else {
                //check if an open message alert exists and remove it
                if ($('#ravealertheader.open-msg').length == 1){
                    $('#ravealertheader.open-msg').remove();
                }
            }

            //Calls callAjax every 1 minute
            setTimeout(callAjax, 60000 * 1); 
        });
    })();


});