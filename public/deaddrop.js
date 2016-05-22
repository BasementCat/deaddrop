(function($) {
    Dropzone.autoDiscover = false;

    var make_url = function(query_arg) {
        var query = [];
        for (var key in query_arg) {
            if (query_arg.hasOwnProperty(key)) {
                query.push(key + '=' + encodeURIComponent(query_arg[key]));
            }
        }

        var url = [
            window.location.protocol,
            '//',
            window.location.host,
            window.location.pathname
        ];
        if (query.length) {
            url.push('?');
            url.push(query.join('&'));
        }
        return url.join('');
    };

    var make_alert = function(id, text, cls) {
        var container = $('#alert-container');
        if (container.find('#' + id).length) {
            return;
        }

        container.append([
            '<div id="', id || '', '" class="', cls || '', '">',
            text, '</div>'
        ].join(''));
    };

    var dz_config = {
        url: 'index.php',
        dictDefaultMessage: 'Drag and drop files here to upload, or click to select files to upload',
    };

    $.extend(dz_config, window.deaddrop.dropzone_config);
    $.extend(dz_config, {
        init: function() {
            this.on('success', function(file, resp) {
                var url = make_url({'hash': resp});
                make_alert(
                    resp,
                    [
                        'Uploading more? Save this link to keep all your files together! ',
                        '<a href="', url, '">', url, '</a>'
                    ].join(''),
                    'info'
                );
            });
        },
        sending: function(file, xhr, formData) {
            formData.append("attribution", $('#attribution').val());
            formData.append("hash", window.deaddrop.hash);
        },
    });

    var dz = new Dropzone('div#dropzone', dz_config);
})(jQuery);
