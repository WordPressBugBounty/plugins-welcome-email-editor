/**
 * Swift SMTP settings page.
 */
(function ($) {
	var data = window.weedSettings;
	var form = document.querySelector(".weed-settings-form");

	if (!form || !data) return;

	/* Tabs
	   ========================================================================== */

	var tabs = document.querySelectorAll(".weed-settings-page .heatbox-tab-nav-item");
	var panels = document.querySelectorAll(".weed-settings-page .heatbox-admin-panel");
	var resetButton = document.querySelector(".weed-reset-settings-button");

	/**
	 * Keep the tab after saving or resetting the settings.
	 *
	 * @param {string} tab The tab name.
	 */
	function rememberTab(tab) {
		var referer = form.querySelector('[name="_wp_http_referer"]');

		if (referer) {
			// Don't show the "Settings reset." notice again after saving.
			referer.value = referer.value.split("#")[0].replace(/&weed-reset=1/, "") + "#" + tab;
		}

		if (resetButton) {
			resetButton.href = resetButton.href.split("#")[0] + "#" + tab;
		}
	}

	/**
	 * Show a tab.
	 *
	 * @param {string} tab The tab name.
	 */
	function showTab(tab) {
		var exists = [].some.call(tabs, function (item) {
			return item.dataset.panel === tab;
		});

		if (!exists) tab = "smtp";

		[].forEach.call(tabs, function (item) {
			item.classList.toggle("active", item.dataset.panel === tab);
		});

		[].forEach.call(panels, function (panel) {
			panel.style.display = panel.dataset.panel === tab ? "block" : "none";
		});

		rememberTab(tab);
	}

	// Remove the one-time notice flags from the address, so a reload doesn't show them again.
	if (window.history.replaceState && /[?&](weed-reset|settings-updated)=/.test(location.search)) {
		window.history.replaceState(null, "", location.href.replace(/&(weed-reset|settings-updated)=[^&#]*/g, ""));
	}

	window.addEventListener("hashchange", function () {
		showTab(location.hash.slice(1));
	});

	showTab(location.hash.slice(1));

	/* Mailer: SMTP or Mailjet API
	   ========================================================================== */

	var mailerFields = form.querySelectorAll('[name="weed_settings[mailer_type]"]');
	var testButton = document.querySelector(".weed-test-email-box .weed-test-email-button");

	function updateMailer() {
		var checked = form.querySelector('[name="weed_settings[mailer_type]"]:checked');
		var mailer = checked ? checked.value : "smtp";

		[].forEach.call(form.querySelectorAll("[data-show-when-mailer-type]"), function (el) {
			el.hidden = el.dataset.showWhenMailerType !== mailer;
		});

		if (testButton) {
			testButton.dataset.emailType = "mailjet_api" === mailer ? "test_mailjet_api_email" : "test_smtp_email";
		}
	}

	[].forEach.call(mailerFields, function (field) {
		field.addEventListener("change", updateMailer);
	});

	updateMailer();

	// Suggest the usual port for the selected encryption.
	var ports = { "": "25", ssl: "465", tls: "587" };

	[].forEach.call(form.querySelectorAll('[name="weed_settings[smtp_encryption]"]'), function (field) {
		field.addEventListener("change", function () {
			var port = form.querySelector('[name="weed_settings[smtp_port]"]');

			if (port && ports.hasOwnProperty(field.value)) {
				port.value = ports[field.value];
			}
		});
	});

	/* Email logging: "Keep Logs For" only matters while logging is on
	   ========================================================================== */

	var loggingField = document.getElementById("weed_settings--enable_email_logging");
	var retentionRow = form.querySelector(".weed-logs-retention");

	if (loggingField && retentionRow) {
		loggingField.addEventListener("change", function () {
			var input = retentionRow.querySelector("input");

			retentionRow.classList.toggle("row-disabled", !loggingField.checked);

			// Read-only (not disabled), so the value is still saved.
			if (input) input.readOnly = !loggingField.checked;
		});
	}

	/* Template tags: click to copy
	   ========================================================================== */

	/**
	 * Copy text to the clipboard (with a fallback for non-secure sites).
	 *
	 * @param {string} text The text.
	 */
	function copyText(text) {
		if (navigator.clipboard && window.isSecureContext) {
			navigator.clipboard.writeText(text);
			return;
		}

		var textarea = document.createElement("textarea");

		textarea.value = text;
		textarea.style.position = "fixed";
		textarea.style.opacity = "0";
		document.body.appendChild(textarea);
		textarea.select();

		try {
			document.execCommand("copy");
		} catch (e) {}

		document.body.removeChild(textarea);
	}

	[].forEach.call(document.querySelectorAll(".tags-heatbox"), function (box) {
		var status = box.querySelector(".action-status");
		var timer;

		[].forEach.call(box.querySelectorAll(".tags-wrapper code"), function (tag) {
			function copy() {
				copyText(tag.textContent);

				if (!status) return;

				clearTimeout(timer);
				status.classList.add("is-shown");

				timer = setTimeout(function () {
					status.classList.remove("is-shown");
				}, 1500);
			}

			tag.addEventListener("click", copy);

			tag.addEventListener("keydown", function (e) {
				if ("Enter" === e.key || " " === e.key) {
					e.preventDefault();
					copy();
				}
			});
		});
	});

	/* Test emails
	   ========================================================================== */

	var nonces = {
		admin_new_user_notif_email: data.nonces.adminWelcomeEmail,
		user_welcome_email: data.nonces.userWelcomeEmail,
		reset_password_email: data.nonces.resetPasswordEmail,
		test_smtp_email: data.nonces.testSmtpEmail,
		test_mailjet_api_email: data.nonces.testMailjetApiEmail,
	};

	/**
	 * Show the result next to the test button.
	 *
	 * @param {HTMLElement} notice The notice element.
	 * @param {string} message The message.
	 * @param {boolean} success Whether it's a success message.
	 */
	function showNotice(notice, message, success) {
		if (!notice) return;

		notice.textContent = message;
		notice.classList.toggle("is-success", success);
		notice.classList.toggle("is-error", !success);
		notice.classList.add("is-shown");
	}

	/**
	 * Save the settings form in the background.
	 *
	 * @return {Promise} Resolves when saved, rejects when saving failed.
	 */
	function saveSettings() {
		return window
			// Not form.action: the form has an input named "action", which shadows the property.
			.fetch(form.getAttribute("action"), {
				method: "POST",
				body: new FormData(form),
				credentials: "same-origin",
				// options.php answers with a redirect back to the page: no need to load it.
				redirect: "manual",
			})
			.then(function (response) {
				if ("opaqueredirect" !== response.type && !response.ok) {
					throw new Error("save failed");
				}
			});
	}

	[].forEach.call(document.querySelectorAll(".weed-test-email-button"), function (button) {
		var notice = button.parentNode.querySelector(".weed-test-email-notice");

		button.addEventListener("click", function () {
			var emailType = button.dataset.emailType;
			var request = {
				action: "weed_test_emails",
				email_type: emailType,
				nonce: nonces[emailType],
			};
			var recipient = document.getElementById("weed_settings--test_smtp_recipient_email");

			if (button.classList.contains("is-loading")) return;

			if (("test_smtp_email" === emailType || "test_mailjet_api_email" === emailType) && recipient) {
				request.to_email = recipient.value;
			}

			button.classList.add("is-loading");

			if (notice) notice.classList.remove("is-shown");

			// The test uses the saved settings, so save what's on screen first.
			saveSettings()
				.then(function () {
					return $.ajax({
						url: window.ajaxurl,
						type: "POST",
						dataType: "json",
						data: request,
					}).then(
						function (r) {
							var success = !!(r && r.success);

							showNotice(notice, r && r.data ? String(r.data) : data.i18n.error, success);
						},
						function (jqXHR) {
							var r = jqXHR.responseJSON;

							showNotice(notice, r && r.data ? String(r.data) : data.i18n.error, false);
						}
					);
				})
				.catch(function () {
					showNotice(notice, data.i18n.saveError, false);
				})
				.then(function () {
					button.classList.remove("is-loading");
				});
		});
	});

	/* Reset
	   ========================================================================== */

	if (resetButton) {
		resetButton.addEventListener("click", function (e) {
			if (!window.confirm(data.i18n.resetSettings)) {
				e.preventDefault();
			}
		});
	}
})(jQuery);
