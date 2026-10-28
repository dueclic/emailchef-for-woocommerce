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

    function debugPage() {
        syncPanel();

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
