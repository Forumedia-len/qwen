$(document).ready(function() {
    $('main').on('click', '.item-upd', function(event) {
        if ($(this).closest('tr').hasClass('edit')) {
            let arr = $('form', $(this).closest('tr')).serializeArray();
            arr.push({
                name: 'item_id',
                value: $('[name=item_id]', $(this).closest('td')).val()
            });
            arr.push({
                name: 'action',
                value: 'CList'
            });
            arr.push({
                name: 'update',
                value: 'Y'
            });
            arr.push({
                name: 'list',
                value: $('.list-table').data('list')
            });
            var th = $(this);
            $.post(BASE_URL, arr).done(function(data) {
                if (!$.isEmptyObject(data)) {
                    th.closest('tr').removeClass('edit');
                    $('form input', th.closest('tr')).prop('readonly', true);
                }
            });
        } else {
            $(this).closest('tr').addClass('edit');
            $('form input', $(this).closest('tr')).prop('readonly', false);
        }
    })
    $('main').on('click', '.item-del', function(event) {
        let arr = [];
        arr.push({
            name: 'item_id',
            value: $('[name=item_id]', $(this).closest('td')).val()
        });
        arr.push({
            name: 'action',
            value: 'CList'
        });
        arr.push({
            name: 'delete',
            value: 'Y'
        });
        arr.push({
            name: 'list',
            value: $('.list-table').data('list')
        });
        $.post(BASE_URL, arr).done(function(data) {
            if (!$.isEmptyObject(data)) {
                window.location.href = BASE_URL;
            }
        });
    });
    $('main').on('click', '.list-insert .btn', function(event) {
        let arr = $(this).closest('form').serializeArray();
        arr.push({
            name: 'list',
            value: $('.list-table').data('list')
        });
        $.post(BASE_URL, arr).done(function(data) {
            if (!$.isEmptyObject(data)) {
                window.location.href = BASE_URL;
            }
        });
    });
    $('main').on('click', '[name=fix_all]', function() {
        let chk = $(this).prop('checked');
        $('[name="fix"]').prop('checked', chk);
    })
    $('main').on('click', '[name=fix]', function() {
        if (!$(this).prop('checked')) {
            $('[name="fix_all"]').prop('checked', false);
        }
    })
    // работа с sql
    $('main').on('click', '.sql .btn', function() {
        var field = $('.sql [name=field_base]').val();
        var command = $('.sql [name=command_sql]').val();
        var name_list = $('.list-table').data('list');
        var list = [];
        $('[name="fix"]:checked').each(function() {
            list.push($(this).val());
        })
        var frec = function(i) {
            $('.sql .btn').removeClass('btn-primary').addClass('btn-danger');
            var data1 = [{
                name: 'action',
                value: 'CBD'
            }, {
                name: 'field_base',
                value: field
            }, {
                name: 'command_sql',
                value: command
            }, {
                name: 'id',
                value: list[i].toString(8)
            }, {
                name: 'list',
                value: name_list
            }];
            $.post(BASE_URL, data1).done(function(data) {
                let st = $('[name="site"]', '#line_' + list[i]).val();
                let dt = JSON.parse(data);
                $('.msg').append('<hr/>');
                $('.msg').append('<div> num - ' + (+list[i] + 1) + ', site - ' + st + '</div>');
                $('.msg').append('<div> Запрос: ' + dt.query + '</div>');
                $('.msg').append('<div> Результат: ' + dt.result + '</div>');
                if (!$.isEmptyObject(dt.error)) {
                    $('.msg').append('<div class="alert alert-danger"> Ошибки: ' + dt.error + '</div>');
                }
                if (i + 1 < list.length) {
                    ++i;
                    frec(i);
                } else {
                    $('.sql .btn').removeClass('btn-danger').addClass('btn-primary');
                }
            }).fail(function(data) {
                let st = $('[name="site"]', '#line_' + list[i]).val();
                $('.msg').append('hr');
                $('.msg').append('<div> num - ' + (+list[i] + 1) + ', site - ' + st + '</div>');
                $('.msg').append('<div class="alert alert-danger">' + data + '</div>');
                if (i + 1 < list.length) {
                    ++i;
                    frec(i);
                } else {
                    $('.sql .btn').removeClass('btn-danger').addClass('btn-primary');
                }
            })
        }
        if (list.length > 0) {
            frec(0);
        }
    })
    $('[name="replase_sql"]').on('input', function() {
        $.post(BASE_URL, [{
            name: 'action',
            value: 'CSave'
        }, {
            name: 'text',
            value: $(this).val()
        }, {
            name: 'file',
            value: 'replase_part/site.sql'
        }]).done(function(data) {});
    })
    $('[name="command_sql"]').on('input', function() {
        $.post(BASE_URL, [{
            name: 'action',
            value: 'CSave'
        }, {
            name: 'text',
            value: $(this).val()
        }, {
            name: 'file',
            value: 'sql.txt'
        }]).done(function(data) {});
    })
    $('[name="command_php"]').on('input', function() {
        $.post(BASE_URL, [{
            name: 'action',
            value: 'CSave'
        }, {
            name: 'text',
            value: $(this).val()
        }, {
            name: 'file',
            value: 'php.txt'
        }]).done(function(data) {});
    })
    // работа с php
    $('main').on('click', '.php .btn', function() {
        var site = $('.php [name=site]').val();
        var host = $('.php [name=host]').val();
        var path = $('.php [name=path]').val();
        var login = $('.php [name=login]').val();
        var pass = $('.php [name=password]').val();
        var protocol = $('.php [name=protocol]').val();
        var command = $('.php [name=command_php]').val();
        var name_list = $('.list-table').data('list');
        var files = [];
        $('.php [name="files[]"]:checked').each(function() {
            files.push({
                name: 'files[]',
                value: $(this).val()
            })
        });
        var list = [];
        $('[name="fix"]:checked').each(function() {
            list.push($(this).val());
        })
        var frec1 = function(i) {
            $('.php .btn').removeClass('btn-primary').addClass('btn-danger');
            var data1 = files;
            data1.push({
                name: 'id',
                value: list[i].toString(8)
            });
            data1.push({
                name: 'site',
                value: site
            });
            data1.push({
                name: 'host',
                value: host
            });
            data1.push({
                name: 'path',
                value: path
            });
            data1.push({
                name: 'login',
                value: login
            });
            data1.push({
                name: 'pass',
                value: pass
            });
            data1.push({
                name: 'command_php',
                value: command
            });
            data1.push({
                name: 'action',
                value: 'CFTP'
            });
            data1.push({
                name: 'list',
                value: name_list
            });
            data1.push({
                name: 'protocol',
                value: protocol
            });
            $.post(BASE_URL, data1).done(function(data) {
                let st = $('[name="site"]', '#line_' + list[i]).val();
                let dt = JSON.parse(data);
                $('.msg').append('<hr/>');
                $('.msg').append('<div> num - ' + (+list[i] + 1) + ', site - ' + st + '</div>');
                $('.msg').append('<div> Переданные файлы: ' + dt.files + '</div>');
                $('.msg').append('<div> Полученые файлы результата: ' + dt.downloads + '</div>');
                $('.msg').append('<div> Директория файлов результата: ' + dt.downloads_dir + '</div>');
                $('.msg').append('<div> Результат: ' + dt.result + '</div>');
                if (!$.isEmptyObject(dt.error)) {
                    $('.msg').append('<div class="alert alert-danger"> Ошибки: ' + dt.error + '</div>');
                }
                if (!$.isEmptyObject(dt.messages)) {
                    $('.msg').append('<div class="alert alert-info"> Сообщения:<br/>' + dt.messages + '</div>');
                }
                if (i + 1 < list.length) {
                    ++i;
                    frec1(i);
                } else {
                    $('.php .btn').removeClass('btn-danger').addClass('btn-primary');
                }
            }).fail(function(data) {
                let st = $('[name="site"]', '#line_' + list[i]).val();
                $('.msg').append('hr');
                $('.msg').append('<div> num - ' + (+list[i] + 1) + ', site - ' + st + '</div>');
                $('.msg').append('<div class="alert alert-danger">' + data + '</div>');
                if (i + 1 < list.length) {
                    ++i;
                    frec1(i);
                } else {
                    $('.php .btn').removeClass('btn-danger').addClass('btn-primary');
                }
            })
        }
        if (list.length > 0) {
            frec1(0);
        }
    })
    // сохранки
    $('main').on('click', '.saves .btn', function() {
        var site = $('.saves [name=site]').val();
        var host = $('.saves [name=host]').val();
        var path = $('.saves [name=path]').val();
        var login = $('.saves [name=login]').val();
        var pass = $('.saves [name=password]').val();
        var base = $('.saves [name=field_base]').val();
        var flg_base = $('.saves [name=chk_base]:checked').length == 1;
        var flg_site = $('.saves [name=chk_site]:checked').length == 1;
        var name_list = $('.list-table').data('list');
        var protocol = $('.saves [name=protocol]').val();
        var list = [];
        $('[name="fix"]:checked').each(function() {
            list.push($(this).val());
        })
        var frec2 = function(i) {
            $('.saves .btn').removeClass('btn-primary').addClass('btn-danger');
            var data1 = [];
            data1.push({
                name: 'id',
                value: list[i].toString(8)
            });
            data1.push({
                name: 'site',
                value: site
            });
            data1.push({
                name: 'host',
                value: host
            });
            data1.push({
                name: 'path',
                value: path
            });
            data1.push({
                name: 'login',
                value: login
            });
            data1.push({
                name: 'pass',
                value: pass
            });
            data1.push({
                name: 'base',
                value: base
            });
            if (flg_base) data1.push({
                name: 'chk_base',
                value: 'Y'
            });
            if (flg_site) data1.push({
                name: 'chk_site',
                value: 'Y'
            });
            data1.push({
                name: 'action',
                value: 'CBack'
            });
            data1.push({
                name: 'list',
                value: name_list
            });
            data1.push({
                name: 'protocol',
                value: protocol
            });
            $.post(BASE_URL, data1).done(function(data) {
                let st = $('[name="site"]', '#line_' + list[i]).val();
                let dt = JSON.parse(data);
                $('.msg').append('<hr/>');
                $('.msg').append('<div> num - ' + (+list[i] + 1) + ', site - ' + st + '</div>');
                $('.msg').append('<div> Дамп БД: ' + dt.dump_sql + '</div>');
                $('.msg').append('<div> Архив сайта: ' + dt.zip_site + '</div>');
                if (!$.isEmptyObject(dt.error)) {
                    $('.msg').append('<div class="alert alert-danger"> Ошибки: ' + dt.error + '</div>');
                }
                if (!$.isEmptyObject(dt.messages)) {
                    $('.msg').append('<div class="alert alert-info"> Сообщения:<br/>' + dt.messages + '</div>');
                }
                if (i + 1 < list.length) {
                    ++i;
                    frec2(i);
                } else {
                    $('.saves .btn').removeClass('btn-danger').addClass('btn-primary');
                }
            }).fail(function(data) {
                let st = $('[name="site"]', '#line_' + list[i]).val();
                $('.msg').append('hr');
                $('.msg').append('<div> num - ' + (+list[i] + 1) + ', site - ' + st + '</div>');
                $('.msg').append('<div class="alert alert-danger">' + data + '</div>');
                if (i + 1 < list.length) {
                    ++i;
                    frec2(i);
                } else {
                    $('.saves .btn').removeClass('btn-danger').addClass('btn-primary');
                }
            })
        }
        if (list.length > 0) {
            frec2(0);
        }
    })
    $('main').on('click', '.replase .btn', function() {
        var site = $('.replase [name=site]').val();
        var host = $('.replase [name=host]').val();
        var path = $('.replase [name=path]').val();
        var login = $('.replase [name=login]').val();
        var pass = $('.replase [name=password]').val();
        var base = $('.replase [name=field_base]').val();
        var flg_base = $('.replase [name=chk_sql_replase]:checked').length == 1;
        var flg_site = $('.replase [name=chk_php_replase]:checked').length == 1;
        var name_list = $('.list-table').data('list');
        var protocol = $('.replase [name=protocol]').val();
        var list = [];
        $('[name="fix"]:checked').each(function() {
            list.push($(this).val());
        })
        var frec3 = function(i) {
            $('.replase .btn').removeClass('btn-primary').addClass('btn-danger');
            var data1 = [];
            data1.push({
                name: 'id',
                value: list[i].toString(8)
            });
            data1.push({
                name: 'site',
                value: site
            });
            data1.push({
                name: 'host',
                value: host
            });
            data1.push({
                name: 'path',
                value: path
            });
            data1.push({
                name: 'login',
                value: login
            });
            data1.push({
                name: 'pass',
                value: pass
            });
            data1.push({
                name: 'base',
                value: base
            });
            if (flg_base) data1.push({
                name: 'chk_base',
                value: 'Y'
            });
            if (flg_site) data1.push({
                name: 'chk_site',
                value: 'Y'
            });
            data1.push({
                name: 'action',
                value: 'CReplase'
            });
            data1.push({
                name: 'list',
                value: name_list
            });
            data1.push({
                name: 'protocol',
                value: protocol
            });
            $.post(BASE_URL, data1).done(function(data) {
                let st = $('[name="site"]', '#line_' + list[i]).val();
                let dt = JSON.parse(data);
                $('.msg').append('<hr/>');
                $('.msg').append('<div> num - ' + (+list[i] + 1) + ', site - ' + st + '</div>');
     			$('.msg').append('<div> Загрузки (Файлы - сохранения): ' + dt.downloads + '</div>');
                $('.msg').append('<div> Результат: ' + dt.result + '</div>');
                if (!$.isEmptyObject(dt.error)) {
                    $('.msg').append('<div class="alert alert-danger"> Ошибки: ' + dt.error + '</div>');
                }
                if (!$.isEmptyObject(dt.messages)) {
                    $('.msg').append('<div class="alert alert-info"> Сообщения:<br/>' + dt.messages + '</div>');
                }
                if (i + 1 < list.length) {
                    ++i;
                    frec3(i);
                } else {
                    $('.replase .btn').removeClass('btn-danger').addClass('btn-primary');
                }
            }).fail(function(data) {
                let st = $('[name="site"]', '#line_' + list[i]).val();
                $('.msg').append('hr');
                $('.msg').append('<div> num - ' + (+list[i] + 1) + ', site - ' + st + '</div>');
                $('.msg').append('<div class="alert alert-danger">' + data + '</div>');
                if (i + 1 < list.length) {
                    ++i;
                    frec3(i);
                } else {
                    $('.replase .btn').removeClass('btn-danger').addClass('btn-primary');
                }
            })
        }
        if (list.length > 0) {
            frec3(0);
        }
    })
})