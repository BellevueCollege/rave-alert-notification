/**
 * Web Component Used to Display Alerts
 */
class BCEmergencyAlert extends HTMLElement {

    constructor() {
        super();

        // Get the element using this template
        const templateEl = document.getElementById('bc-alert-template');

        // Validate it exists
        if ( ! templateEl) {
            console.warn('Template not found');
            return;
        }

        // Create node based on template
        const template = templateEl.content.cloneNode(true);
        this.appendChild(template);

        // Define template attributes
        this._container        = this.querySelector('#ravealertheader');
        this._icon             = this.querySelector('#ravealerticon');
        this._messageContainer = this.querySelector('#ravealertmessage');
        this._messageHeading   = this.querySelector('#ravealertevent');
        this._messageText      = this.querySelector('#ravealertcontent');
    }

    // Public API for Showing and Updating the Alert
    show( options = {} ) {
        if ( ! this._container ) return;

        // Set up default options for the show method
        const {
            alertClass      = '',
            messageHeading  = '',
            messageText     = '',
            messageInfoURL  = '',
            contentOverride = false,
            iconOverride    = false
        } = options;

        // Sanitize and add classes to the container
        const safeClasses = this._sanitizeClasses( alertClass );
        this._container.className = 'container';
        if ( safeClasses.length ) {
            this._container.classList.add( ...safeClasses );
        }

        // Set entire content when override is present (must be sanitized in PHP)
        if ( contentOverride ) {
            this._messageContainer.innerHTML = contentOverride;
        } else {

            // Set icon when icon override is present (must be sanitized in PHP)
            if ( iconOverride ) {
                this._icon.innerHTML = iconOverride;
            }

            // Set Heading
            this._messageHeading.textContent = messageHeading;

            // Set Message Text
            this._messageText.textContent = messageText + ' ';

            // Build link safely and add to message text
            if (messageInfoURL) {
                const link = document.createElement('a');
                link.href = messageInfoURL;
                link.textContent = 'More Information';
                this._messageText.appendChild(link);
            }
        }

        // Set Accessibility Attributes
        this._container.setAttribute('role', 'alert');
        this._container.setAttribute('aria-live', 'assertive');
        this._container.setAttribute('aria-atomic', 'true');

        // Show the alert
        this.removeAttribute('hidden');

        // Scroll into view after 100ms (delay prevents failure)
        setTimeout(() => {
            this.scrollIntoView({ 
                behavior: 'smooth', 
                block: 'start' 
            });
        }, 100 );
    }

    // Public API for hiding the alert
    hide() {
        if ( ! this._container ) return;
        if ( ! this.isVisible() ) return;

        // Hide the alert
        this.setAttribute('hidden', '');
    }

    // Check if the alert is visible
    isVisible() {
        return ! this.hasAttribute('hidden');
    }

    // Sanitize classes using regex
    _sanitizeClasses(classString) {
        const SAFE = /^[a-z0-9_-]+$/i;
        return String(classString)
            .split(/\s+/)
            .filter(Boolean)
            .filter(t => SAFE.test(t));
    }
}

export default BCEmergencyAlert;