jQuery(document).ready(function($) {
    // Sidebar navigation
    $('.config-edit-view').on('click', '.sidebar-nav', function (evt, isInitial) {
        evt.preventDefault();
        var sidebar = $(this).closest('.config-edit-sidebar');
        var contentArea = sidebar.next('.config-edit-content');

        sidebar.find('li').removeClass('active');
        $(this).parent().addClass('active');

        contentArea.find('> .config-section').hide();
        var selector = this.getAttribute('href');
        $(selector).show();

        // Scroll to top
        if (!isInitial) {
            window.scrollTo({ top: 0, behavior: 'smooth' });
        }
    });

    // Open config edit view
    $('.edit-trigger').on('click', function () {
        var key = $(this).data('key');
        $('.config-list-view').hide();
        var editView = $('#edit-view-' + key);
        editView.show();
        editView.prev('.back-to-list').show();
        // Trigger first sidebar item
        editView.find('.sidebar-nav').first().trigger('click', [true]);
    });

    // Back to list
    $('.back-to-list').on('click', function (evt) {
        evt.preventDefault();
        $('.config-edit-view').hide();
        $('.back-to-list').hide();
        $('.config-list-view').show();
    });

    // Toggle Enabled option from main list table
    $('.config-list-view').on('change', '.config-enable-checkbox', function () {
        var self = this;
        var $card = $(this).closest('.config-card');
        var data = new FormData();
        data.append('security_headers_nonce', $('#security_headers_nonce').val());
        data.append('action', 'dtdr_enable_config');
        data.append('key', this.value);
        data.append('enabled', this.checked);

        // since 2.8 ajaxurl is always defined in the admin header and points to admin-ajax.php
        fetch(ajaxurl, {
            method: 'POST',
            body: data,
        }).then((response) => response.json())
            .then((data) => {
                if (!data || !data.success) {
                    console.error('Error saving active state of config.', data);
                    self.checked = !self.checked;
                } else {
                    // Update status text in card
                    $card.find('.status-text').text(self.checked ? 'Enabled' : 'Disabled');
                }
            })
            .catch((error) => {
                console.error('Error saving active state of config.', error);
            });
    });

    // Edit form submission
    $('.config-edit-view form').on('submit', function (evt) {
        if (evt) {
            evt.preventDefault();
        }
        const form = evt.target;
        const formdata = new FormData(form);

        const key = formdata.get('key');

        // since 2.8 ajaxurl is always defined in the admin header and points to admin-ajax.php
        fetch(ajaxurl, {
            method: 'POST',
            body: formdata,
        })
            .then((response) => response.json())
            .then((data) => {
                if (data.success) {
                    // if successful, update the main card
                    const card = $('#config-list-row-' + key);
                    card.find('.name').text(formdata.get('name'));
                    card.find('.provider').text(formdata.get('provider'));
                    const isChecked = formdata.get('enabled') === 'on';
                    card.find('.config-enable-checkbox').prop('checked', isChecked);
                    card.find('.status-text').text(isChecked ? 'Enabled' : 'Disabled');

                    // back to list
                    $('.config-edit-view').hide();
                    $('.back-to-list').hide();
                    $('.config-list-view').show();
                } else {
                    console.error('Error saving state of config:', data.message);
                }
            })
            .catch((error) => {
                console.error('Error saving state of config:', error);
            });
    });

    // Toggle appropriate fields for each provider
    $('.config-edit-view').on('change', '.provider', function () {
        var form = $(this).closest('form');
        form.find('.provider-field').hide();
        form.find('.provider-' + $(this).val()).show();
    });

    // Reset last exported progress
    $('.config-edit-view').on('click', '.last-exported-value button', function () {
        var btn = $(this);
        var configKey = btn.data('configKey');
        var dataType = btn.data('dataType');
        var data = new FormData();
        data.append('security_headers_nonce', $('#security_headers_nonce').val());
        data.append('action', 'dtdr_reset_progress');
        data.append('key', configKey);
        data.append('dataType', dataType);

        fetch(ajaxurl, {
            method: 'POST',
            body: data,
        }).then((response) => response.json())
            .then(() => {
                btn.closest('.field-group').fadeOut();
            })
            .catch((error) => {
                console.error('Error resetting progress:', error);
            });
    });

    $('.config-edit-view').on('click', '.view-logs-trigger', function () {
        var btn = $(this);
        btn.closest('.field-group').find('.log-messages').toggle();
    });
});
