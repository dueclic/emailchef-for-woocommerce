/**
 * WooCommerce emailChef Plugins
 */

var WC_Emailchef = function ($) {


    var namespace = 'wc_emailchef';

    var $createList;
    var $selList;
    var $apiUser;
    var $apiPass;
    var $saveNewList;
    var $newListName;
    var $newListDesc;
    var $policyList;
    var $landingList;
    var $fpageList;
    var $langChange;
    var $disconnectAccount

    return {
        go: go
    };

    function getElements() {
        $langChange = $("#" + prefixed_setting("lang"));
        $disconnectAccount = $("#emailchef-disconnect");
        $createList = $("#" + prefixed_setting("create_list"));
        $selList = $("#" + prefixed_setting("list"));
        $newListName = $("#" + prefixed_setting("new_name"));
        $newListDesc = $("#" + prefixed_setting("new_description"));
        $saveNewList = $("#" + prefixed_setting("new_save"));
        $policyList = $("#" + prefixed_setting("policy_type"));
        $landingList = $("#" + prefixed_setting("landing_page"));
        $fpageList = $("#" + prefixed_setting("fuck_page"));
    }

    function formContent(env) {

        var $nextAll = $apiPass.closest("tr").nextAll("tr");

        if (env === 'show') {
            $nextAll.fadeIn();
        }

        else {
            $nextAll.fadeOut();
        }

    }

    function formPolicy(env) {

        if (env === 'show') {
            $policyList.closest("tr").css('display', 'table-row');
        }

        else {
            $policyList.find("option[value='dopt']").attr('selected', 'selected');
            $policyList.closest("tr").fadeOut();
        }

    }

    function loadLists(apiUser, apiPass, list_id) {
        $selList.attr("disabled", "disabled");

        $.post(
            ajaxurl,
            {
                'action': '' + prefixed_setting('lists'),
                'data': {
                    'api_user': apiUser,
                    'api_pass': apiPass
                }
            },
            function (response) {

                var result = $.parseJSON(response);

                if (result.type === 'success') {

                    var list_exists = false;
                    var prev = $selList.select2('val');

                    $selList.select2('destroy');
                    $selList.removeAttr('disabled');
                    $selList.empty();
                    $.each(result.lists, function (key, val) {
                        if (key === prev){
                            list_exists = true;
                        }
                        $selList
                            .prepend($('<option></option>')
                                .attr('value', key)
                                .text(val));
                    });

                    $selList.select2();

                    if (list_id !== -1) {
                        console.log("Seleziono lista creata: " + list_id);
                        $selList.select2('val', list_id).trigger("change");
                    } else {
                        if (list_exists){
                            $selList.select2('val', prev).trigger("change");
                        }
                    }

                }

            }
        );

    }

    function mainListChanges() {

        $selList.closest("tr").hide();

    }

    function triggerElements() {

        $langChange.on("change", function (evt) {

            $.ajax({
                type: 'POST',
                url: ajaxurl,
                data: {
                    'action': '' + prefixed_setting('changelanguage'),
                    'data': {
                        lang: $langChange.val()
                    }
                },
                dataType: 'json',
                success: function (response) {

                    if (confirm(response.msg)) {
                        location.reload();
                    }

                },
                error: function (jxqr, textStatus, thrown) {
                },
                complete: function () {
                }
            });

        });

        $createList.on("click", function (evt) {
            evt.preventDefault();

            if ($(".tr-info-color").length)
                return;

            $createList.closest("tr").after('<tr class="tr-info-color" valign="top">' +
                '<th scope="row" class="titledesc">' +
                '<label for="wc_emailchef_new_name">' + wcec.list_name + '</label>' +
                '</th>' +
                '<td class="forminp forminp-text">' +
                '<input name="wc_emailchef_new_name" id="wc_emailchef_new_name" type="text" dir="ltr" style="min-width:350px;" value="" class="" placeholder="' + wcec.list_name_placeholder + '">‎' +
                '</td>' +
                '</tr>' +
                '<tr class="tr-info-color" valign="top">' +
                '<th scope="row" class="titledesc">' +
                '<label for="wc_emailchef_new_description">' + wcec.list_description + '</label>' +
                '</th>' +
                '<td class="forminp forminp-text">' +
                '<input name="wc_emailchef_new_description" id="wc_emailchef_new_description" type="text" dir="ltr" style="min-width:350px;" value="" class="" placeholder="' + wcec.list_description_placeholder + '">‎' +
                '</td>' +
                '</tr>' +
                '<tr class="tr-info-color" valign="top">' +
                '<th scope="row" class="titledesc">' +
                '<label for="wc_emailchef_new_save">' + wcec.create_label + '</label>' +
                '</th>' +
                '<td class="forminp forminp-text">' +
                '<button name="wc_emailchef_save" class="button-primary woocommerce-save-button" id="wc_emailchef_new_save" >' + wcec.create + '</button>‎' +
                '&nbsp;&nbsp;' +
                '<button name="wc_emailchef_undo" class="button woocommerce-undo-button" id="wc_emailchef_undo_save" >' + wcec.undo + '</button>‎' +
                '</td>' +
                '</tr>' +
                '<tr class="tr-info-color" valign="top">' +
                '<td colspan="2">' + wcec.info + '</td>' +
                '</tr>');
        });

        $(document).on("click", "#" + prefixed_setting("new_save"), function (evt) {
            evt.preventDefault();
            addList($apiUser.val(), $apiPass.val(), $("#" + prefixed_setting("new_name")).val(), $("#" + prefixed_setting("new_description")).val());
        });

        $(document).on("click", "#" + prefixed_setting("undo_save"), function (evt) {
            evt.preventDefault();
            $(".tr-info-color").remove();
        });

        $policyList.on("change", function (evt) {

            evt.preventDefault();

            if ($(this).val() === 'sopt') {
                $landingList.closest("tr").fadeOut();
                $fpageList.closest("tr").fadeOut();
            }
            else {
                $landingList.closest("tr").fadeIn();
                $fpageList.closest("tr").fadeIn();
            }

        });

    }

    function addList(apiUser, apiPass, listName, listDesc) {

        $selList.attr("disabled", "disabled");

        $.post(
            ajaxurl,
            {
                'action': '' + prefixed_setting('add_list'),
                'data': {
                    'api_user': apiUser,
                    'api_pass': apiPass,
                    'list_name': listName,
                    'list_desc': listDesc
                }
            },
            function (response) {
                console.log(response);
                var result = $.parseJSON(response);

                if (result.type === 'success') {
                    $(".tr-info-color").remove();
                    loadLists(apiUser, apiPass, result.list_id);
                }
                else {
                    alert(result.msg);
                }
            }
        );

    }

    function prefixed_setting(suffix) {
        return namespace + "_" + suffix;
    }

    function go() {
        $(document).on("click", $disconnectAccount, function (evt) {
            if (confirm(wcec.disconnect_confirm)){
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
    }
}(jQuery);
