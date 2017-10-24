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
                    .removeClass('hidden')
                    .html(data.resultMessage);
                clearInterval(timer);
            } else {
                inProgress.removeClass('hidden');
            }
        }

        function errorCheckHandler(data) {
            $('#in-progress').addClass('hidden');

            $('#response-error')
                .removeClass('hidden')
                .html(data.responseJSON.resultMessage);
            clearInterval(timer);
        }
    }
});