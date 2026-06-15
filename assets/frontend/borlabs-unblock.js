(function (config) {
	if (!config || !config.src || !config.blockerId) {
		return;
	}

	var loaded = false;

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

	document.addEventListener(
		'borlabs-cookie-content-unblocked[' + config.blockerId + ']',
		loadModule
	);

	if (
		window.BorlabsCookie &&
		window.BorlabsCookie.Consents &&
		typeof window.BorlabsCookie.Consents.hasConsentForContentBlockerId === 'function' &&
		window.BorlabsCookie.Consents.hasConsentForContentBlockerId(config.blockerId)
	) {
		loadModule();
	}
})(window.weMaveVideoBorlabs || null);
