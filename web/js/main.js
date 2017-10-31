$(document).ready(function () {

    function hideResponses() {
        $('#responses').find('div').addClass('hidden');
    }

    function showInProgress() {
        $('#in-progress')
            .removeClass('hidden');
    }

    function hideInProgress() {
        $('#in-progress')
            .addClass('hidden');
    }

    function showSetError(text) {
        $('#response-error')
            .html(text)
            .removeClass('hidden');
    }

    function showSetSuccess(text) {
        $('#response-success')
            .html(text)
            .removeClass('hidden');
    }

    //FIXME: fix HTML5 validation or use a new plugin

    $('#run').on('click', function (ev) {
        ev.preventDefault();

        var form = $('#main-form'),
            url = form.attr('action'),
            formData = form.serializeArray();

        hideResponses();
        showInProgress();

        $.ajax({
            type: "POST",
            url: url,
            data: formData,
            success: successRunHandler,
            error: errorRunHandler,
            dataType: 'text'
        });
    });

    $('#check').on('click', function (ev) {
        ev.preventDefault();

        hideResponses();

        successRunHandler();
    });

    function errorRunHandler(data) {
        hideResponses();
        showSetError(data.responseText);
    }

    function successRunHandler() {
        var form = $('#main-form'),
            url = form.data('check-action'),
            formData = form.serializeArray();

        var runCheck = function () {
            $.ajax({
                type: "GET",
                url: url,
                data: formData,
                success: successCheckHandler,
                error: errorCheckHandler,
                dataType: 'json'
            });
        };

        runCheck();
        var timer = setInterval(runCheck, 20000);

        function successCheckHandler(data) {
            if (data.finished) {
                hideInProgress();

                showSetSuccess(data.resultMessage);
                clearInterval(timer);
            } else {
                showInProgress();
            }
        }

        function errorCheckHandler(data) {
            hideInProgress();

            showSetError(data.responseJSON.resultMessage);
            clearInterval(timer);
        }
    }

    $('#drop').on('click', function (ev) {
        ev.preventDefault();

        var databaseName = $('#databases').val();
        if (!databaseName || !confirm('Are you sure you want to delete "' + databaseName + '"?')) {
            return;
        }

        var form = $('#main-form'),
            url = form.attr('action'),
            formData = form.serializeArray();

        hideResponses();
        showInProgress();

        $.ajax({
            type: "DELETE",
            url: url,
            data: formData,
            success: successDropHandler,
            error: errorDropHandler,
            complete: hideInProgress,
            dataType: 'text'
        });

        function successDropHandler(data) {
            var databases = $('#databases');
            databases.find("option[value='" + databases.val() + "']").remove();
            databases.selectpicker('refresh').val(null).trigger('change');

            showSetSuccess(data);
        }

        function errorDropHandler(data) {
            showSetError(data.responseText);
        }
    })
});