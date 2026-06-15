(function (config) {
	if (!config || !config.src || !config.serviceId) {
		return;
	}

	var loaded = false;

	function restoreBlockedPlayers() {
		document.querySelectorAll('.we-mave-video-rcb-blocked').forEach(function (blocked) {
			var template = blocked.querySelector('template.we-mave-video-rcb-template');
			if (!template || !template.content || !template.content.firstChild) {
				return;
			}

			var fragment = document.createDocumentFragment();
			while (template.content.firstChild) {
				fragment.appendChild(template.content.firstChild);
			}

			blocked.replaceWith(fragment);
		});
	}

	function loadModule() {
		if (loaded) {
			restoreBlockedPlayers();
			return;
		}

		loaded = true;

		var script = document.createElement('script');
		script.type = 'module';
		script.src = config.src;
		document.body.appendChild(script);

		restoreBlockedPlayers();
	}

	function waitForConsent() {
		return window.consentApi && typeof window.consentApi.consent === 'function'
			? window.consentApi.consent(config.serviceId)
			: Promise.resolve();
	}

	waitForConsent().then(loadModule);

	document.addEventListener('click', function (event) {
		var button = event.target.closest('[data-we-mave-rcb-load]');
		if (!button) {
			return;
		}

		event.preventDefault();
		waitForConsent().then(loadModule);
	});
})(window.weMaveVideoRcb || null);
