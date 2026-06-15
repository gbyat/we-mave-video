(function (config) {
	if (!config || !config.src || !config.blockerId) {
		return;
	}

	var loaded = false;

	function hasConsent() {
		if (!window.BorlabsCookie || !window.BorlabsCookie.Consents) {
			return false;
		}

		var consents = window.BorlabsCookie.Consents;

		if (
			typeof consents.hasConsentForContentBlockerId === 'function' &&
			consents.hasConsentForContentBlockerId(config.blockerId)
		) {
			return true;
		}

		if (
			config.serviceId &&
			typeof consents.hasConsent === 'function' &&
			consents.hasConsent(config.serviceId)
		) {
			return true;
		}

		return false;
	}

	function loadModule() {
		if (loaded) {
			return;
		}

		loaded = true;

		var script = document.createElement('script');
		script.type = 'module';
		script.src = config.src;
		document.body.appendChild(script);
	}

	function maybeLoadModule() {
		if (hasConsent()) {
			loadModule();
		}
	}

	document.addEventListener(
		'borlabs-cookie-content-unblocked[' + config.blockerId + ']',
		loadModule
	);

	window.addEventListener('borlabs-cookie-after-init', maybeLoadModule);
	window.addEventListener('borlabs-cookie-handle-unblock', maybeLoadModule);
	window.addEventListener('borlabs-cookie-consent-saved', maybeLoadModule);

	maybeLoadModule();
})(window.weMaveVideoBorlabs || null);
