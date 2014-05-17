var FACTOID = FACTOID || (function() {
    return {
        load: function(game) {
            $.fn.editable.defaults.mode = 'inline';
            $('.editable-input').parents('form').removeClass('form-inline');

            load(game);
        }
    };
}());

function toggleEditable(name, inputId, type) {
    var input = $('#' + name + "_" + inputId);
    if (!input.hasClass("editable")) {
        input.editable({
            highlight: false,
            toggle: 'manual',
            onblur: 'ignore',
            rows: 4,
            type: type,
            showbuttons: false,
            innerClass: 'editable-input-textarea',
            savenochange: true
        });
        input.editable('enable');
        input.editable('toggle');
    } else {
        input.editable('toggle');
        input.editable('disable');
        input.editable('destroy');
        input.css({display: 'block'});
    }
}

function load(game) {
    formattedGame = game === null ? null : game.toLowerCase();
    $('#factoidtable').hide();
    $('#gamename').text(game === null ? "All databases" : game);
    var posting = $.post('factoid/get', {db: formattedGame}, "json");
    posting.done(function(data) {
        loadDatabase(game, data);
    });
}

function loadDatabase(game, data) {
    var json = JSON.parse(data);
    var menu = $('#dbListings');
    menu.empty();
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
        html += '<tr><td><em>' + factoid.id + '</em></td>';
        html += '<td><strong id="name_' + factoid.id + '" data-pk="' + factoid.id + '">' + factoid.name + '</strong></td>';

        if (game === null) {
            html += '<td><p id="game_' + factoid.id + '" data-pk="' + factoid.id + '">' + factoid.game + '</p></td>';
            $('#game_column').show();
        } else {
            $('#game_column').hide();
        }

        html += '<td><p id="content_' + factoid.id + '" data-pk="' + factoid.id + '">' + factoid.content + '</p></td>';
        html += '<td>';

        if (perms.edit) {
            html += '<button id="editbutton_' + factoid.id + '" data-factoid="' + factoid.id + '" type="button" class="btn btn-xs btn-warning editbutton">Edit</button>';
            html += '<button id="savebutton_' + factoid.id + '" data-factoid="' + factoid.id + '" type="button" class="btn btn-xs btn-success savebutton">Save</button>';
        }

        if (perms.delete) {
            if (perms.edit) {
                html += '<br><br>';
            }
            html += '<button id="deletebutton_' + factoid.id + '" data-factoid="' + factoid.id + '" type="button" class="btn btn-xs btn-danger">Delete</button>';
        }

        html += '</td>';
        html += '</tr>';
        table.append(html);
        if (perms.edit) {
            $('#savebutton_' + factoid.id).hide();
        }

        if (perms.edit || perms.delete) {
            $('#action_column').show();
        } else {
            $('#action_column').hide();
        }
    }

    $('.editbutton').on("click", function(e) {
        e.stopPropagation();
        toggleEditable("name", $(this).attr('data-factoid'), "text");
        toggleEditable("game", $(this).attr('data-factoid'), "text");
        toggleEditable("content", $(this).attr('data-factoid'), "textarea");
        $(this).hide();
        $('#savebutton_' + $(this).attr('data-factoid')).show();
    });

    $('.savebutton').on("click", function(e) {
        e.stopPropagation();
        toggleEditable("name", $(this).attr('data-factoid'), "text");
        toggleEditable("game", $(this).attr('data-factoid'), "text");
        toggleEditable("content", $(this).attr('data-factoid'), "textarea");
        $(this).hide();
        $('#editbutton_' + $(this).attr('data-factoid')).show();
        $id = $(this).attr('data-factoid');
        $.post('/factoid/edit', {id: $(this).attr('data-factoid'), name: $('#name_' + $(this).attr('data-factoid')).editable('getValue'), content: $('#content_' + $(this).attr('data-factoid')).editable('getValue')}, function(data) {
            $('#flashwarning').hide();
            $('#flashwarning').text('Edit of ' + $id + ": " + data);
            $('#flashwarning').show();
        });
    });

    table.show();
}