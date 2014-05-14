var FACTOID = FACTOID || (function() {
    return {
        load: function(game, perms) {
            $.fn.editable.defaults.mode = 'inline';
            $('.editable-input').parents('form').removeClass('form-inline');

            $('#dbListings').append('<li id="loader"><a href="/">Loading</a></li>');

            var posting = $.post('factoid/get', {db: game}, "json");
            $('#factoidtable').hide();

            posting.done(function(data) {
                $('#loader').remove();
                $('#dbListings').append('<li><a href="/factoid">All</a></li>');

                var json = JSON.parse(data);
                var menu = $('#dbListings');

                for (var i = 0; i < json.games.length; i++) {
                    menu.append('<li><a href="/factoid">' + json.games[i].displayname + '</a></li>');
                }
                var table = $("#factoidtable");
                var factoids = json.factoids;

                for (var i = 0; i < factoids.length; i++) {
                    var factoid = factoids[i];
                    var html = "";
                    html += '<tr><td><em>' + factoid.id + '</em></td>';
                    html += '<td><strong id="name_' + factoid.id + '" data-pk="' + factoid.id + '">' + factoid.name + '</strong></td>';

                    if (game === null) {
                        html += '<td><p id="game_' + factoid.id + '" data-pk="' + factoid.id + '">' + factoid.game + '</p></td>';
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
                }
                table.show();
                $('#progressbar').hide();
            });

            $('.editbutton').click(function(e) {
                console.log("Edit fired");
                e.stopPropagation();
                toggleEditable("name", $(this).attr('data-factoid'), "text");
                toggleEditable("game", $(this).attr('data-factoid'), "text");
                toggleEditable("content", $(this).attr('data-factoid'), "textarea");
                $(this).hide();
                $('#savebutton_' + $(this).attr('data-factoid')).show();
            });

            $('.savebutton').click(function(e) {
                e.stopPropagation();
                toggleEditable("name", $(this).attr('data-factoid'), "text");
                toggleEditable("game", $(this).attr('data-factoid'), "text");
                toggleEditable("content", $(this).attr('data-factoid'), "textarea");
                $(this).hide();
                $('#editbutton_' + $(this).attr('data-factoid')).show();
            });

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
                        innerClass: 'editable-input-textarea'
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
        }
    };
}());