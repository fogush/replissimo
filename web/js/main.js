$(document).ready(function () {
    $('#run').on('click', function (ev) {
        ev.preventDefault();

        $('#responses').find('div').addClass('hidden');

        var form = $('#main-form'),
            url = form.attr('action'),
            formData = form.serializeArray();
            
        $.ajax({
            type: "POST",
            url: url,
            data: formData,
            error: errorRunHandler,
            success: successRunHandler,
            dataType: 'text'
        });
    });

    function errorRunHandler(data) {
        $('#response-error')
            .html(data.responseText)
            .removeClass('hidden');
    }

    function successRunHandler() {
        $('#in-progress')
            .removeClass('hidden');

        var form = $('#main-form'),
            url = form.data('check-action'),
            formData = form.serializeArray();

        var timer = setInterval(function () {
            $.ajax({
                type: "GET",
                url: url,
                data: formData,
                error: errorCheckHandler,
                success: successCheckHandler,
                dataType: 'json'
            });
        }, 20000);

        function errorCheckHandler(data) {
            $('#in-progress').addClass('hidden');

            $('#response-error')
                .removeClass('hidden')
                .html(data.responseJSON.resultMessage);
            clearInterval(timer);
        }

        function successCheckHandler(data) {
            if (data.finished) {
                $('#in-progress').addClass('hidden');

                $('#response-finish')
                    .removeClass('hidden')
                    .html(data.resultMessage);
                clearInterval(timer);
            }
        }
    }

});