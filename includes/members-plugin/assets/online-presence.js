(function () {
	'use strict';

	var config = window.MPOnlinePresence || {};
	if (!config.ajaxUrl || !config.nonce) {
		return;
	}

	var endpoint = config.ajaxUrl;
	var nonce = config.nonce;
	var loggedIn = Boolean(config.loggedIn);
	var interval = Math.max(30000, Number(config.interval || 60000));

	function visibleDots() {
		return Array.prototype.slice.call(document.querySelectorAll('.mp-presence-dot[data-user-id]'));
	}

	function setState(dot, state) {
		var online = state === 'online';
		var label = online ? (config.onlineLabel || 'Online') : (config.offlineLabel || 'Offline');
		dot.classList.toggle('mp-presence-dot--online', online);
		dot.classList.toggle('mp-presence-dot--offline', !online);
		dot.setAttribute('aria-label', label);
		dot.setAttribute('title', label);
		dot.setAttribute('data-presence', online ? 'online' : 'offline');
	}

	function post(data) {
		var body = new URLSearchParams();
		Object.keys(data).forEach(function (key) {
			if (key === 'user_ids' && Array.isArray(data[key])) {
				data[key].forEach(function (id) {
					body.append('user_ids[]', id);
				});
			} else {
				body.append(key, data[key]);
			}
		});

		return window.fetch(endpoint, {
			method: 'POST',
			credentials: 'same-origin',
			headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
			body: body.toString()
		}).then(function (response) {
			if (!response.ok) {
				throw new Error('Presence request failed.');
			}
			return response.json();
		});
	}

	function pollPresence() {
		var dots = visibleDots();
		if (!dots.length || document.visibilityState === 'hidden') {
			return Promise.resolve();
		}

		var ids = [];
		dots.forEach(function (dot) {
			var id = dot.getAttribute('data-user-id');
			if (id && ids.indexOf(id) === -1 && ids.length < 100) {
				ids.push(id);
			}
		});

		if (!ids.length) {
			return Promise.resolve();
		}

		return post({
			action: 'mp_online_presence_poll',
			nonce: nonce,
			user_ids: ids
		}).then(function (response) {
			if (!response || !response.success || !response.data) {
				return;
			}
			var states = response.data;
			dots.forEach(function (dot) {
				var id = dot.getAttribute('data-user-id');
				if (Object.prototype.hasOwnProperty.call(states, id)) {
					setState(dot, states[id]);
				}
			});
		}).catch(function () {
			// Use the server-rendered state if AJAX is unavailable.
		});
	}

	function heartbeat() {
		if (!loggedIn || document.visibilityState === 'hidden') {
			return Promise.resolve();
		}

		return post({
			action: 'mp_online_presence_heartbeat',
			nonce: nonce
		}).catch(function () {
			// The regular WordPress request tracking remains as a fallback.
		});
	}

	function refresh() {
		pollPresence();
		heartbeat();
	}

	// The page request itself already marked the member as online on the server, so
	// no heartbeat is sent on load (that was one extra admin-ajax request per page view).
	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', pollPresence);
	} else {
		pollPresence();
	}

	var lastRefresh = Date.now();
	window.setInterval(function () {
		lastRefresh = Date.now();
		refresh();
	}, interval);
	document.addEventListener('visibilitychange', function () {
		// Refresh when a tab becomes visible again, but not more often than the interval.
		if (document.visibilityState === 'visible' && Date.now() - lastRefresh >= interval) {
			lastRefresh = Date.now();
			refresh();
		}
	});
}());
