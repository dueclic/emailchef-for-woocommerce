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

        $(document).on("click", "#wc_emailchef_sync_now", function (evt) {
            $.post(wcec.ajax_manual_sync_url, {}, function (response) {
                location.reload();
            });
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

    function debugPage() {

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
