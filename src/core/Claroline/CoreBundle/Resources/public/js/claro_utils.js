(function () {

    $('#bootstrap-modal').modal({
        show: false,
        backdrop: false
    });

    $('#bootstrap-modal').on('hidden', function(){
        /*$('#modal-login').empty();
        $('#modal-body').show();*/
        //the page must be reloaded or it'll break dynatree
        if ($('#modal-login').find('form').attr('id') == 'login_form'){
            window.location.reload();
        }
    })

    var utils = this.ClaroUtils = {};

    utils.ajaxAuthenticationErrorHandler = function (callBack) {
        $.ajax({
            type: 'GET',
            url: Routing.generate('claro_security_login'),
            cache: false,
            success: function (data) {
                $('#modal-body').hide();
                $('#modal-login').append(data);
                $('#bootstrap-modal').modal('show');
                $('#login_form').submit(function (e) {
                    e.preventDefault();
                    $.ajax({
                        type: 'POST',
                        url: Routing.generate('claro_security_login_check'),
                        cache: false,
                        data: $('#login_form').serialize(),
                        success: function (data) {
                            $('#bootstrap-modal').modal('hide');
                            callBack();
                        }
                    });
                });
            }
        });
    }


    utils.sendRequest = function (route, successHandler, completeHandler) {
        var url = '';
        'string' == typeof route ? url = route : url = Routing.generate(route.name, route.parameters);
        $.ajax({
            type: 'GET',
            url: url,
            cache: false,
            success: function (data, textStatus, jqXHR) {
                if ('function' == typeof successHandler) {
                    successHandler(data, textStatus, jqXHR);
                }
            },
            complete: function(data){
                if ('function' == typeof completeHandler){
                    completeHandler(data)}
            },
            error: function(xhr, e){
                xhr.status == 403 ?
                    utils.ajaxAuthenticationErrorHandler(function () {
                        'function' == typeof successHandler ?
                            utils.sendRequest(route, successHandler) :
                            window.location.reload();
                    }) :
                    alert('error for the route '+route+' (check your js debugger)');
                    console.error(xhr);
                    console.error(e);
            }
        });
    }

    utils.sendForm = function(route, form, successHandler){
        var url = '';
        'string' == typeof route ? url = route : url = Routing.generate(route.name, route.parameters);
        var formData = new FormData(form);
        var xhr = new XMLHttpRequest();
        xhr.open('POST', url, true);
        xhr.setRequestHeader('X_Requested_With', 'XMLHttpRequest');
        xhr.onload = function (e) {
            successHandler(xhr);
        };
        xhr.onreadystatechange = function() {
            if (xhr.readyState == 4){
                if (xhr.status == 403){
                    window.location.reload();
                }
            }
        }
        xhr.send(formData);
    }

    /**
     * Returns the check value of a combobox form.
     */
    utils.getCheckedValue = function (radioObj) {
        if (!radioObj) {
            return '';
        }

        var radioLength = radioObj.length;

        if (radioLength == undefined) {
            if (radioObj.checked) {
                return radioObj.value;
            } else {
                return '';
            }
        }

        for (var i = 0; i < radioLength; i++) {
            if (radioObj[i].checked) {
                return radioObj[i].value;
            }
        }
        return '';
    }

    utils.findLoadedJsPath = function (filename) {
        return $('script[src*="'+filename+'"]').attr('src');
    }

    utils.splitCookieValue = function(cookie) {
        var values = new Object();
        var cookieArray = cookie.split(';');

        for (var i=0; i<cookieArray.length; i++){
            var key = cookieArray[i].split('=')[0];
            var value = cookieArray[i].split('=')[1];
            value.replace(/^\s*|\s*$/g,'');
            values[key] = value;
        }

        return values;
    }
})();