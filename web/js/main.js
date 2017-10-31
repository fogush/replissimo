$(document).ready(function () {
    //FIXME: fix HTML5 validation or use a new plugin

    $('#run').on('click', function (ev) {
        ev.preventDefault();

        $('#responses').find('div').addClass('hidden');

        var form = $('#main-form'),
            url = form.attr('action'),
            formData = form.serializeArray();

        $('#in-progress')
            .removeClass('hidden');

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

    function hideResponses() {
        $('#responses').find('div').addClass('hidden');
    }

    function errorRunHandler(data) {
        hideResponses();
        $('#response-error')
            .html(data.responseText)
            .removeClass('hidden');
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
            var inProgress = $('#in-progress');
            if (data.finished) {
                inProgress.addClass('hidden');

                $('#response-finish')
                    .html(data.resultMessage)
                    .removeClass('hidden');
                clearInterval(timer);
            } else {
                inProgress.removeClass('hidden');
            }
        }

        function errorCheckHandler(data) {
            $('#in-progress').addClass('hidden');

            $('#response-error')
                .html(data.responseJSON.resultMessage)
                .removeClass('hidden');
            clearInterval(timer);
        }
    }

    $('#drop').on('click', function (ev) {
        ev.preventDefault();

        var databaseName = $('#databases').val();
        if (!databaseName || !confirm('Are you sure you want to delete "' + databaseName + '"')) {
            return;
        }

        var form = $('#main-form'),
            url = form.attr('action'),
            formData = form.serializeArray();

        hideResponses();

        $.ajax({
            type: "DELETE",
            url: url,
            data: formData,
            success: successDropHandler,
            error: errorDropHandler,
            dataType: 'text'
        });

        function successDropHandler(data) {

            var databases = $('#databases');
            databases.find("option[value='" + databases.val() + "']").remove();
            databases.selectpicker('refresh').val(null).trigger('change');

            $('#response-success')
                .html(data)
                .removeClass('hidden');
        }

        function errorDropHandler(data) {
            $('#response-error')
                .html(data)
                .removeClass('hidden');
        }
    })
});