var FACTOID = FACTOID || (function() {
    return {
        load: function(game) {
            $.fn.editable.defaults.mode = 'inline';
            $('.editable-input').parents('form').removeClass('form-inline');
            load(game);
        }
    };
}());
function load(game) {
    formattedGame = game === null ? null : game.toLowerCase();
    $('#factoidtable').hide();
    $('#gamename').text(game === null ? "All databases" : game);
    if (game === null) {
        $('#game_column').show();
    } else {
        $('#game_column').hide();
    }
    $('#action_column').hide();
    var posting = $.post('factoid/get', {db: formattedGame}, "json");
    posting.done(function(data) {
        loadDatabase(game, data);
    });
}

function loadDatabase(game, data) {
    var json = JSON.parse(data);
    var menu = $('#dbListings');
    menu.empty();
    menu.append('<li><a onClick=load(null)>All</a></li>');
    for (var i = 0; i < json.games.length; i++) {
        menu.append('<li><a onClick=load("' + json.games[i].displayname + '")>' + json.games[i].displayname + '</a></li>');
    }
    var table = $("#factoidtable");
    table.empty();
    var factoids = json.factoids;
    for (var i = 0; i < factoids.length; i++) {
        var factoid = factoids[i];
        var perms = json.perms;
        var html = "";
        var $id = factoid.id;
        html += '<tr><td><em>' + $id + '</em></td>';
        html += '<td><strong id="name_' + $id + '" class="factoidid_' + $id + '" data-pk="' + $id + '">' + factoid.name + '</strong></td>';
        if (game === null) {
            html += '<td><p id="game_' + $id + '" class="factoidid_' + $id + '" data-pk="' + $id + '">' + factoid.game + '</p></td>';
            $('#game_column').show();
        } else {
            $('#game_column').hide();
        }

        html += '<td><p id="content_' + $id + '" class="factoidid_' + $id + '" data-pk="' + $id + '">' + factoid.content + '</p></td>';
        html += '<td>';
        if (perms.edit) {
            html += '<button id="editbutton_' + $id + '" data-factoid="' + $id + '" type="button" class="btn btn-xs btn-warning editbutton">Edit</button>';
            html += '<button id="savebutton_' + $id + '" data-factoid="' + $id + '" type="button" class="btn btn-xs btn-success savebutton">Save</button>';
        }

        if (perms.delete) {
            if (perms.edit) {
                html += '<br><br>';
            }
            html += '<button id="deletebutton_' + $id + '" data-factoid="' + $id + '" type="button" class="btn btn-xs btn-danger">Delete</button>';
        }

        html += '</td>';
        html += '</tr>';
        table.append(html);
        if (perms.edit) {
            $('#savebutton_' + $id).hide();
            $input = $('#content_' + $id);
            $input.editable({
                highlight: false,
                toggle: 'manual',
                onblur: 'ignore',
                rows: 4,
                type: 'textarea',
                showbuttons: true,
                send: 'auto',
                pk: $id,
                url: '/factoid/edit',
                ajaxOptions: {
                    dataType: 'json'
                },
                success: function(data, config) {
                    $('#editbutton_' + $id).show();
                    $('#flashwarning').text('Edit of ' + $id + ": " + JSON.stringify(data));
                    $('#flashwarning').show();
                    $('#flashwarning').delay(5000).fadeOut(1000, function() {
                        $('#flashwarning').hide();
                    });
                },
                error: function(data, config) {
                    $('#editbutton_' + $id).show();
                    $('#flashwarning').text('Edit of ' + $id + ": " + JSON.stringify(data));
                    $('#flashwarning').show();
                    $('#flashwarning').delay(5000).fadeOut(1000, function() {
                        $('#flashwarning').hide();
                    });
                }
            });
            $input.on('hidden', function(e) {
                $('#editbutton_' + $id).show();
            });
            $input.editable('disable');
        }

        if (perms.edit || perms.delete) {
            $('#action_column').show();
        } else {
            $('#action_column').hide();
        }
    }

    $('.editbutton').on("click", function(e) {
        e.stopPropagation();
        $id = $(this).attr('data-factoid');
        var input = $('#content_' + $id);
        input.editable('enable');
        input.editable('toggle');
        $(this).hide();
    });
    table.show();
}