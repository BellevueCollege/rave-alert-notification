/*
* Pull data from xml feed
*
*/


jQuery( document ).ready( function( $ ) {

    (function callAjax() {

        $.ajax({
            method: 'GET',
            url: rest_php_variables['rest_url'],
        }).done(function (alert_info) {
            console.log(alert_info);
            /* *******************
            * CAP Rave Alert
            * ********************/ 
            // verify data (identifier) returned is a string 
            if ( typeof alert_info['rave_alert'].identifier == 'string' ) {
                console.log('active rave alert');

                var more_info_message = rest_php_variables['more_info_message'];

                // Checks if current page is homepage and severity is minor OR severity is not minor regardless of page
                if ( ( rest_php_variables['is_homepage'] == true && alert_info['rave_alert'].severity.toLowerCase() == 'minor' ) || (alert_info['rave_alert'].severity.toLowerCase() !== 'minor') ) {   
                    //check if #ravealertheader does not exist in <body>
                    if ($('#ravealertheader').length == 0) {
                        $('body').prepend('<div id="ravealertheader" class="container alert"><div class="row"><div class="col-sm-2"><span class="glyphicon glyphicon-warning-sign" aria-hidden="true"></span></div><div class="col-sm-10"><div id="ravealertmessage"><h2 id="ravealertevent">' + 'Loading Alert...' + '</h2><p>' + 'Loading headline...' + ' ' + more_info_message + '</p></div></div></div></div>');
                    }

                    //Get alert data
                    $.ajax({
                        method: 'GET',
                            url: rest_php_variables['rest_url'] + alert_info['rave_alert'].identifier,
                    }).done(function (data) {
                        console.log(data);
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

                } else { // If severity is minor and not on the homepage then remove #ravealertheader if there is one
                    $('#ravealertheader').remove();
                }

            } else { // if string is not returned
                //check if an alert exists and remove it
                if ($('#ravealertheader').length == 1 && alert_info['college_wide_notif_msg'].active == false ){
                    $('#ravealertheader').remove();
                }
            }

            /* *******************
            * College-wide notification message
            * ********************/ 
            if ( alert_info['college_wide_notif_msg'].active == true) {
                console.log('active college alert');

                //check if #ravealertheader does not exist in <body>
                if ($('#ravealertheader').length == 0) {
                    $('body').prepend('<div id="ravealertheader" class="container alert"><div class="row"><div class="col-sm-2"><span class="glyphicon glyphicon-warning-sign" aria-hidden="true"></span></div><div class="col-sm-10"><div id="ravealertmessage"><h2 id="ravealertevent">' + 'Loading Alert...' + '</h2><p>' + 'Loading headline...' + ' ' + more_info_message + '</p></div></div></div></div>');
                }

                //Get college-wide notification message data
                $.ajax({
                    method: 'GET',
                        url: rest_php_variables['rest_url'] + 'college-wide-notif-msg',
                }).done(function (data) {
                    console.log(data);
                    var msg_output = '';
                    if (typeof data == 'object') {
                        var new_description = data['description'];
                        var new_class = data['class'];
                        msg_output += '<div id="ravealertheader" class="container ' + new_class + ' college-wide-notif-msg"><div class="row"><div class="col-sm-2"><span class="glyphicon glyphicon-warning-sign" aria-hidden="true"></span></div><div class="col-sm-10"><div id="ravealertmessage"><p>' + new_description + '</p></div></div></div></div>';
                    }
                    //Replace #ravealertheader with new output
                    $('#ravealertheader').replaceWith( msg_output );
                }).fail(function(error) {
                    console.log('Error calling RAVE REST API COLLEGE-WIDE NOTIFICATION MESSAGE: ');
                    console.log(error);
                });

            } else { // college-wide notif message isn't active
                //check if an alert exists and remove it
                if ($('#ravealertheader.college-wide-notif-msg').length == 1 && alert_info['rave_alert'] == false){
                    $('#ravealertheader.college-wide-notif-msg').remove();
                }
            }

        }).fail(function(error){
            console.log('Error calling RAVE REST API: ' );
            console.log(error);
        }).always(function() { 
            //Always run even if REST API fails
            //Calls callAjax every 1 minute
            setTimeout(callAjax, 60000 * 1); 
        });
    })();


});