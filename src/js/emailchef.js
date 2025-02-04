/**
 * WooCommerce emailChef Plugins
 */

var WC_Emailchef = function ($) {


    var namespace = 'wc_emailchef';

    return {
        go: go
    };

    function loadLists(list_id) {

        $(".ecwc-new-list-container button").attr("disabled", "disabled");

        $.post(ajaxurl, {
                'action': '' + prefixed_setting('lists')
            },
            function (response) {

                var result = $.parseJSON(response);

                if (result.type === 'success') {

                    var options = [];

                    $.each(result.lists, function (id, text) {
                        options.push({
                            text: text,
                            id: id
                        });
                    });

                    $("#"+prefixed_setting("list")).empty().select2({
                        data: options
                    });

                    $("#"+prefixed_setting("list")).val(list_id).trigger("change");

                }

            }
        );

    }

    function addList(listName, listDesc) {

        $(".ecwc-new-list-container button").attr("disabled", "disabled");

        $.post(
            ajaxurl,
            {
                'action': '' + prefixed_setting('add_list'),
                'data': {
                    'list_name': listName,
                    'list_desc': listDesc
                }
            },
            function (response) {

                $(".ecwc-new-list-container").hide();
                $(".ecwc-new-list-container button").removeAttr("disabled");

                var result = $.parseJSON(response);

                if (result.type === 'success') {
                    loadLists(result.list_id);
                } else {
                    alert(result.msg);
                }
            }
        );

    }

    function prefixed_setting(suffix) {
        return namespace + "_" + suffix;
    }

    function go() {
        $(document).on("click", "#emailchef-disconnect", function (evt) {
            evt.preventDefault();
            if (confirm(wcec.disconnect_confirm)) {
                $.post(
                    ajaxurl,
                    {
                        'action': '' + prefixed_setting('disconnect'),
                        'data': {}
                    },
                    function (response) {
                        var result = $.parseJSON(response);

                        if (result.type === 'success') {
                            window.location.reload();
                        }
                    }
                );
            }
        });
        $(document).on("click", "#wc_emailchef_create_list", function (evt) {
            evt.preventDefault();
            $(".ecwc-new-list-container").show();
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
    }
}(jQuery);
