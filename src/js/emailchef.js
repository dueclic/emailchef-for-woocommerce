/**
 * WooCommerce Emailchef Plugins
 */

var WC_Emailchef = function ($) {


    var namespace = wcec.namespace;

    return {
        settings: settings,
        debugPage: debugPage
    };

    function loadLists(list_id) {

        $(".ecwc-new-list-container button").attr("disabled", "disabled");

        $.post(wcec.ajax_lists_url, {}, function (response) {

            if (response.success) {

                var options = [];

                $.each(response.data.lists, function (id, text) {
                    options.push({
                        text: text,
                        id: id
                    });
                });

                $("#" + prefixed_setting("list")).empty().select2({
                    data: options
                });

                $("#" + prefixed_setting("list")).val(list_id).trigger("change");

            } else {
                alert(response.data.message);
            }

        });

    }

    function addList(listName, listDesc) {

        $(".ecwc-new-list-container button").attr("disabled", "disabled");

        $.post(wcec.ajax_add_list_url, {
            'data': {
                'list_name': listName,
                'list_desc': listDesc
            }
        }, function (response) {

            $(".ecwc-new-list-container button").removeAttr("disabled");

            if (response.success) {
                $(".ecwc-new-list-container").hide();
                loadLists(response.data.list_id);
            } else {
                alert(response.data.message);
            }
        });

    }

    function prefixed_setting(suffix) {
        return namespace + "_" + suffix;
    }

    function settings() {
        syncPanel();
        abandonedCartsPanel();
        $(document).on("click", "#emailchef-disconnect", function (evt) {
            evt.preventDefault();
            if (confirm(wcec.disconnect_confirm)) {
                $.post(wcec.ajax_disconnect_url, {}, function (response) {

                    if (response.success) {
                        window.location.reload();
                    } else {
                        alert(response.data.message);
                    }
                });
            }
        });
        $(document).on("click", "#wc_emailchef_create_list", function (evt) {
            evt.preventDefault();
            $(".ecwc-new-list-container").toggle();
        });

        $(document).on("click", ".ecwc-new-list-container .woocommerce-undo-button", function (evt) {
            evt.preventDefault();
            $(".ecwc-new-list-container").hide();
        });
        $(document).on("click", ".ecwc-new-list-container .woocommerce-save-button", function (evt) {
            evt.preventDefault();
            $(this).attr("disabled", "disabled");
            addList(
                $("#" + prefixed_setting("new_name")).val(),
                $("#" + prefixed_setting("new_description")).val()
            );
        });

        var showPasswordButton = document.getElementById('showPassword');
        var hidePasswordButton = document.getElementById('hidePassword');
        var consumerSecretInput = document.getElementById(prefixed_setting("consumer_secret"));

        if (showPasswordButton) {
            showPasswordButton.addEventListener('click', () => {
                consumerSecretInput.setAttribute('type', 'text');
                showPasswordButton.style.display = 'none';
                hidePasswordButton.style.display = 'flex';
            });
        }

        if (hidePasswordButton) {
            hidePasswordButton.addEventListener('click', () => {
                consumerSecretInput.setAttribute('type', 'password');
                showPasswordButton.style.display = 'flex';
                hidePasswordButton.style.display = 'none';
            });
        }

    }

    function syncPanel() {
        var syncButton = $('#emailchef-button-initial-sync');
        var syncMessages = wcec.initial_sync_messages;
        if (!syncButton.length) return;
        var slider = $('#emailchef-batch-size');
        var sliderPending = false;
        var savedBatchSize = Number(slider.val());
        slider.on('input', function () {
            sliderPending = true;
            $('#emailchef-batch-size-value').text(slider.val());
            $('#emailchef-batch-save-status').text('');
        }).on('change', function () {
            slider.prop('disabled', true);
            syncButton.prop('disabled', true);
            $.post(wcec.ajax_initial_sync_batch_size_url, { batch_size: slider.val() }, function (response) {
                if (response.success) {
                    savedBatchSize = Number(response.data.batch_size);
                    $('#emailchef-batch-save-status').text(syncMessages.batch_saved);
                } else {
                    $('#emailchef-batch-save-status').text(response.data.message || syncMessages.batch_error_save);
                }
            }).fail(function () {
                $('#emailchef-batch-save-status').text(syncMessages.batch_error_save);
            }).always(function () {
                slider.val(savedBatchSize).prop('disabled', false);
                $('#emailchef-batch-size-value').text(savedBatchSize);
                sliderPending = false;
                updateSyncStatus();
            });
        });
        var syncDialog = document.getElementById('emailchef-sync-dialog');
        function showSyncDialog() {
            if (syncDialog && !syncDialog.open) syncDialog.showModal();
        }
        function renderChunks(job) {
            var body = $('#emailchef-sync-chunks').empty();
            var chunks = job.chunks || [];
            $('#emailchef-sync-chunks-empty').toggle(chunks.length === 0);
            chunks.slice().reverse().forEach(function (chunk) {
                var row = $('<tr>');
                $('<td>').text('#' + chunk.number).appendTo(row);
                $('<td>').text(new Date(chunk.time * 1000).toLocaleString()).appendTo(row);
                $('<td>').text(chunk.status === 'accepted' ? syncMessages.accepted : syncMessages.batch_error).appendTo(row);
                $('<td>').text(chunk.accepted + ' / ' + chunk.sent).appendTo(row);
                var details = [];
                if (chunk.task_id) details.push(syncMessages.task + ': ' + chunk.task_id);
                if (chunk.warnings) details.push(syncMessages.warnings + ': ' + chunk.warnings);
                if (chunk.warning_details && chunk.warning_details.length) details.push(chunk.warning_details.join('; '));
                if (chunk.message) details.push(chunk.message);
                $('<td>').text(details.join(' | ') || '—').appendTo(row);
                body.append(row);
            });
        }
        $('#emailchef-open-sync-results').on('click', showSyncDialog);
        $('#emailchef-close-sync-results').on('click', function () { if (syncDialog) syncDialog.close(); });
        function updateSyncStatus() {
            $.get(wcec.ajax_initial_sync_status_url, function (response) {
                if (!response.success) return;
                var job = response.data;
                if (!sliderPending && job.batch_size) {
                    savedBatchSize = Number(job.batch_size);
                    slider.val(savedBatchSize);
                    $('#emailchef-batch-size-value').text(savedBatchSize);
                }
                var active = job.status === 'queued' || job.status === 'running';
                var displayStatus = job.stalled ? 'stalled' : job.status;
                $('#emailchef-sync-status').attr('data-status', displayStatus);
                var progress = job.total ? Math.min(100, Math.round(job.processed * 100 / job.total)) : 0;
                if (job.status === 'completed') progress = 100;
                $('#emailchef-sync-progress').val(progress);
                $('#emailchef-sync-message').text(syncMessages[displayStatus] || syncMessages.idle);
                var details = job.status === 'idle' ? '' : syncMessages.list + ': ' + job.list_id + ' | ' + syncMessages.contacts + ': ' + job.processed + ' / ' + job.total + ' (' + progress + '%)';
                if (job.updated_at) details += ' | ' + syncMessages.updated + ': ' + new Date(job.updated_at * 1000).toLocaleString();
                if (job.phase === 'setup' && active) details += ' | ' + syncMessages.setup;
                if (job.message) details += ' | ' + job.message;
                $('#emailchef-sync-details').text(details);
                $('#emailchef-sync-dialog-status').text((syncMessages[displayStatus] || syncMessages.idle) + (details ? ' ' + details : ''));
                renderChunks(job);
                syncButton.prop('disabled', (active && !job.stalled) || sliderPending || $('.emailchef-debug__sync').attr('data-list-available') !== '1').text(job.status === 'error' || job.stalled ? syncMessages.resume : syncMessages.start);
            }).fail(function () {
                $('#emailchef-sync-message').text(syncMessages.unavailable);
            });
        }
        if (syncButton.length) {
            updateSyncStatus();
            window.setInterval(updateSyncStatus, 5000);
        }
        $(document).on('click', '#emailchef-button-initial-sync', function (evt) {
            evt.preventDefault();
            syncButton.prop('disabled', true);
            showSyncDialog();
            $.post(wcec.ajax_manual_sync_url, {}, function (response) {
                if (!response.success) {
                    $('#emailchef-sync-dialog-status').text(response.data.message);
                    syncButton.prop('disabled', false);
                } else {
                    updateSyncStatus();
                }
            }).fail(function () {
                $('#emailchef-sync-message, #emailchef-sync-dialog-status').text(syncMessages.unavailable);
                syncButton.prop('disabled', false);
            });
        });

    }

    function abandonedCartsPanel() {
        var panel = $('#emailchef-abcart-panel');
        if (!panel.length || !wcec.ajax_abandoned_carts_overview_url) return;
        var messages = wcec.abandoned_carts_messages;
        var dialog = document.getElementById('emailchef-abcart-dialog');
        var state = { page: 1, pages: 1, status: '' };

        function safeUrl(url) {
            return /^https?:\/\//i.test(url || '') ? url : '';
        }
        function format(text) {
            var args = Array.prototype.slice.call(arguments, 1);
            return String(text).replace(/%(\d+)\$s|%s/g, function (match, index) {
                return index ? args[Number(index) - 1] : args.shift();
            });
        }
        function when(time) {
            var date = new Date(time * 1000);
            var text;
            try {
                text = date.toLocaleString(undefined, { dateStyle: 'short', timeStyle: 'short' });
            } catch (e) {
                text = date.toLocaleString();
            }
            return $('<time>').attr('datetime', date.toISOString()).text(text);
        }
        function badge(status) {
            return $('<span class="emailchef-debug__abcart-badge">').attr('data-status', status).text(messages[status] || status);
        }
        function productCell(row) {
            var cell = $('<div class="emailchef-debug__abcart-product">');
            var image = safeUrl(row.image_url);
            if (image) {
                $('<img alt="" loading="lazy">').attr('src', image).appendTo(cell);
            } else {
                $('<span class="emailchef-debug__abcart-noimage" aria-hidden="true">').appendTo(cell);
            }
            var text = $('<div>').appendTo(cell);
            var url = safeUrl(row.product_url);
            var name = row.product_name || ('#' + (row.product_id || ''));
            (url ? $('<a target="_blank" rel="noopener">').attr('href', url) : $('<strong>')).text(name).appendTo(text);
            if (row.product_price) $('<span>').text(row.product_price).appendTo(text);
            return cell;
        }
        function renderRecent(rows) {
            var list = $('#emailchef-abcart-recent').empty();
            $('#emailchef-abcart-empty').text(rows.length ? '' : messages.empty).prop('hidden', rows.length > 0);
            list.prop('hidden', rows.length === 0);
            $('#emailchef-abcart-open-history').prop('hidden', rows.length === 0);
            rows.forEach(function (row) {
                var item = $('<li>').append(productCell(row));
                $('<div class="emailchef-debug__abcart-meta">')
                    .append($('<span class="emailchef-debug__abcart-email">').text(row.user_email))
                    .append(when(row.time))
                    .append(badge(row.status))
                    .appendTo(item);
                list.append(item);
            });
        }
        function loadOverview() {
            $.get(wcec.ajax_abandoned_carts_overview_url, function (response) {
                if (!response.success) return;
                var data = response.data;
                $('#emailchef-abcart-stats').attr('aria-busy', 'false');
                $('#emailchef-abcart-pending').text(data.stats.pending);
                $('#emailchef-abcart-synced').text(data.stats.synced);
                $('#emailchef-abcart-errors').text(data.stats.errors).closest('div').attr('data-has-errors', data.stats.errors > 0 ? '1' : '0');
                $('#emailchef-abcart-delay').text(format(messages.delay, data.delay_value, data.delay_unit === 'DAY' ? messages.days : messages.hours));
                $('#emailchef-abcart-next').text(messages.next_run + ': ' + (data.next_run ? new Date(data.next_run * 1000).toLocaleString() : messages.not_scheduled));
                $('#emailchef-abcart-retention').text(format(messages.retention, data.retention_days));
                renderRecent(data.recent || []);
            }).fail(function () {
                $('#emailchef-abcart-stats').attr('aria-busy', 'false');
                $('#emailchef-abcart-empty').text(messages.unavailable).prop('hidden', false);
            });
        }
        function detailsRow(row, id) {
            var box = $('<div class="emailchef-debug__abcart-details">');
            if (row.status === 'legacy') {
                $('<p>').text(messages.legacy_note).appendTo(box);
            } else {
                var image = safeUrl(row.image_url);
                if (image) $('<img alt="">').attr('src', image).appendTo(box);
                var facts = $('<dl>').appendTo(box);
                [['description', row.description], ['product_url', row.product_url], ['image_url', row.image_url]].forEach(function (fact) {
                    $('<dt>').text(messages[fact[0]]).appendTo(facts);
                    $('<dd>').text(fact[1] || '—').appendTo(facts);
                });
            }
            if (row.message) $('<p class="emailchef-debug__abcart-error">').text(row.message).appendTo(box);
            return $('<tr class="emailchef-debug__abcart-detail-row">').attr({ id: id, hidden: true }).append($('<td colspan="5">').append(box));
        }
        function loadHistory() {
            var body = $('#emailchef-abcart-history');
            var table = $('#emailchef-abcart-history-table').attr('aria-busy', 'true');
            $.get(wcec.ajax_abandoned_carts_history_url, { page: state.page, status: state.status }, function (response) {
                if (!response.success) return;
                var data = response.data;
                state.page = data.page;
                state.pages = data.pages;
                body.empty();
                $('#emailchef-abcart-history-empty').text(data.rows.length ? '' : messages.empty).prop('hidden', data.rows.length > 0);
                data.rows.forEach(function (row) {
                    var tr = $('<tr>');
                    $('<td>').append(when(row.time)).appendTo(tr);
                    $('<td class="emailchef-debug__abcart-customer">').text(row.user_email).appendTo(tr);
                    $('<td>').append(productCell(row)).appendTo(tr);
                    $('<td>').append(badge(row.status)).appendTo(tr);
                    var detailsId = 'emailchef-abcart-detail-' + row.id;
                    var details = detailsRow(row, detailsId);
                    var label = $('<span>').text(messages.details);
                    var context = (row.product_name || '') + ', ' + row.user_email;
                    var toggle = $('<button type="button" class="button-link" aria-expanded="false">').attr('aria-controls', detailsId)
                        .append(label, $('<span class="screen-reader-text">').text(': ' + context))
                        .on('click', function () {
                            var open = details.prop('hidden');
                            details.prop('hidden', !open);
                            toggle.attr('aria-expanded', open ? 'true' : 'false');
                            label.text(open ? messages.hide : messages.details);
                        });
                    $('<td>').append(toggle).appendTo(tr);
                    body.append(tr, details);
                });
                $('#emailchef-abcart-page').text(format(messages.page, data.page, data.pages, data.total));
                $('#emailchef-abcart-prev').prop('disabled', data.page <= 1);
                $('#emailchef-abcart-next-page').prop('disabled', data.page >= data.pages);
            }).fail(function () {
                body.empty();
                $('#emailchef-abcart-history-empty').text(messages.unavailable).prop('hidden', false);
            }).always(function () {
                table.attr('aria-busy', 'false');
            });
        }

        $('#emailchef-abcart-open-history').on('click', function () {
            state.page = 1;
            loadHistory();
            if (dialog && !dialog.open) dialog.showModal();
        });
        $('#emailchef-abcart-close-history').on('click', function () { if (dialog) dialog.close(); });
        $('#emailchef-abcart-filter').on('change', function () {
            state.status = $(this).val();
            state.page = 1;
            loadHistory();
        });
        $('#emailchef-abcart-prev').on('click', function () { if (state.page > 1) { state.page--; loadHistory(); } });
        $('#emailchef-abcart-next-page').on('click', function () { if (state.page < state.pages) { state.page++; loadHistory(); } });

        loadOverview();
        window.setInterval(loadOverview, 60000);
    }

    function debugPage() {
        syncPanel();
        abandonedCartsPanel();

        $(document).on('click', '#emailchef-button-move-abandoned-carts', function (evt) {
            evt.preventDefault();
            var ajaxurl = wcec.ajax_debug_move_abandoned_carts_url;
            $.post(ajaxurl, {},
                function (response) {
                    if (response.success) {
                        console.log("Abandoned carts moved successfully");
                        location.reload();
                    } else {
                        alert(response.data.message);
                    }
                }
            );

        });

        $(document).on('click', '.emailchef-button-force-sync', function (evt) {
            evt.preventDefault();
            var userId = $(this).data('user-id');
            var ajaxurl = wcec.ajax_sync_abandoned_carts_url;
            $.post(ajaxurl, {'only_userid': userId}, function (response) {
                if (response.success) {
                    console.log("Abandoned cart synced successfully");
                    location.reload();
                } else {
                    alert(response.data.message);
                }
            });
        });

        $(document).on('click', '#emailchef-button-rebuild-customfields', function (evt) {
            evt.preventDefault();
            var ajaxurl = wcec.ajax_debug_rebuild_customfields_url;
            $.post(ajaxurl, {}, function (response) {
                if (response.success) {
                    console.log("Recover custom fields successfully");
                    location.reload();
                } else {
                    alert(response.data.message);
                }
            });
        });

    }


}(jQuery);
