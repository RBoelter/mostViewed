describe('Most Viewed Articles plugin tests', function () {

	it('Disable Most Viewed Articles', function () {
		cy.login('admin', 'admin', 'publicknowledge');
		cy.get('ul[id="navigationPrimary"] a:contains("Settings")').click();
		cy.get('ul[id="navigationPrimary"] a:contains("Website")').click();
		cy.get('button[id="plugins-button"]').click();
		// disable plugin if enabled
		cy.get('input[id^="select-cell-mostviewedplugin-enabled"]')
			.then($btn => {
				if ($btn.attr('checked') === 'checked') {
					cy.get('input[id^="select-cell-mostviewedplugin-enabled"]').click();
					cy.get('div[class*="pkp_modal_panel"] button[class*="pkpModalConfirmButton"]').click();
					cy.get('div:contains(\'The plugin "Most Viewed Articles" has been disabled.\')');
				}
			});
	});

	it('Enable Most Viewed Articles', function () {
		cy.login('admin', 'admin', 'publicknowledge');
		cy.get('ul[id="navigationPrimary"] a:contains("Settings")').click();
		cy.get('ul[id="navigationPrimary"] a:contains("Website")').click();
		cy.get('button[id="plugins-button"]').click();
		// Find and enable the plugin
		cy.get('input[id^="select-cell-mostviewedplugin-enabled"]').click();
		cy.get('div:contains(\'The plugin "Most Viewed Articles" has been enabled.\')');
		cy.waitJQuery();
		cy.get('tr[id="component-grid-settings-plugins-settingsplugingrid-category-generic-row-mostviewedplugin"] a[class="show_extras"]').click();
		cy.get('a[id^="component-grid-settings-plugins-settingsplugingrid-category-generic-row-mostviewedplugin-settings-button"]').click();
		// Fill out settings form
		cy.get('form[id="mostViewedSettings"] input[name="mostViewedTitle[en_US]"]').clear().type('Most Viewed automated Test Title');
		cy.get('form[id="mostViewedSettings"] input[name="mostViewedDays"]').clear().type('60');
		cy.get('form[id="mostViewedSettings"] input[name="mostViewedAmount"]').clear().type('10');
		cy.get('form[id="mostViewedSettings"] input[name="mostViewedYears"]').clear().type('25');
		cy.get('form[id="mostViewedSettings"] input[name="mostViewedPosition"]').check();
		// submit settings form
		cy.get('form[id="mostViewedSettings"] button[id^="submitFormButton"]').click();
		cy.waitJQuery();
		cy.get('div:contains(\'Your changes have been saved.\')');
	});
});
