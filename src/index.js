import BCEmergencyAlert from "./classes/BCEmergencyAlertWC.js";

// Connect the web component to the custom elements registry
customElements.define('bc-emergency-alert', BCEmergencyAlert);

// Wait for element definition, then start polling
customElements.whenDefined('bc-emergency-alert').then(() => {
    const alertElement = document.getElementById('bc-emergency-alert');

    // Make sure element is present
    if (!alertElement) return;

    checkForBCAlert( alertElement );
});

function displayManualBCAlertOrHide( alertElement ) {

    // Get manual messages that are injected via PHP
    const openMessageDesc = window.rave_alert_settings.open_message_desc;
    const openMessageClass = window.rave_alert_settings.open_message_class;

    if ( '' !== openMessageDesc && '' !== openMessageClass ) {
        // Show manual alert if present
        alertElement.show({
            alertClass: openMessageClass,
            manualAlert: openMessageDesc // MUST be sanitized via PHP
        });
        return true;
    }

    // Hide the alert if nothing is available
    alertElement.hide();
    return false;
}

async function checkForBCAlert( alertElement ) {
    // Get minutes of current time for cache busting
    const currentTime = new Date();
    const currentHours = String( currentTime.getUTCHours() ).padStart( 2, '0' );
    const currentMinutes = String( currentTime.getUTCMinutes() ).padStart( 2, '0' );
    const cachebuster = `${ currentHours }${ currentMinutes }`;
    
    try {

        // Get the available alerts from REST API endpoint
        const response = await fetch( `${ window.rave_alert_settings.rest_url }alerts/${ cachebuster }` );
        const alertInfo = await response.json();

        // Check for active alert
        const hasActiveAlert = alertInfo && typeof alertInfo.identifier === 'string';

        // Hide alert if there is no active alert identifier
        if ( hasActiveAlert ) {
            const isHomepage = window.rave_alert_settings.is_homepage === '1';
            const severity = alertInfo.severity.toLowerCase();

            // Hide alert if severity is minor and not on homepage
            if ( 'minor' === severity && ! isHomepage ) {
                displayManualBCAlertOrHide( alertElement );
            } else {
                // Build and show alert
                alertElement.show({
                    alertId: alertInfo.identifier,
                    alertClass: alertInfo.message.class,
                    messageHeading: alertInfo.message.event,
                    messageText: alertInfo.message.headline,
                    messageInfoURL: alertInfo.message.more_info
                });
            }
        } else {
            displayManualBCAlertOrHide( alertElement );
        }
        } catch ( error ) {
            console.error( 'Error fetching alert alertInfo:', error );
            displayManualBCAlertOrHide( alertElement );
        }

    setTimeout(() => checkForBCAlert( alertElement ), 60000); // Poll every 1 minute
}
