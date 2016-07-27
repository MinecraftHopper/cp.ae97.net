function parseToText($string) {
    var autolinker = new Autolinker({
        email: false,
        twitter: false,
        stripPrefix: false
    });
    return markdownToHtml(mircToHtml(autolinker.link($string.replace(new RegExp(';;', 'g'), ' <br>'))));
}

function mircToHtml(text) {
    //bold,italics,underline (more could be added.)
    var bui = [
        [/\002([^\002]+)(\002)?/, ["<b>", "</b>"]],
        [/\037([^\037]+)(\037)?/, ["<u>", "</u>"]],
        [/\035([^\035]+)(\035)?/, ["<i>", "</i>"]]
    ];
    for (var i = 0; i < bui.length; i++) {
        var bc = bui[i][0];
        var style = bui[i][1];
        if (bc.test(text)) {
            while (bmatch = bc.exec(text)) {
                var text = text.replace(bmatch[0], style[0] + bmatch[1] + style[1]);
            }
        }
    }
    return text;
}

function ircToMarkdown(text) {
    //bold,italics,underline (more could be added.)
    var bui = [
        [/\002([^\002]+)(\002)?/, ["[b]", "[/b]"]],
        [/\037([^\037]+)(\037)?/, ["[u]", "[/u]"]],
        [/\035([^\035]+)(\035)?/, ["[i]", "[/i]"]]
    ];
    for (var i = 0; i < bui.length; i++) {
        var bc = bui[i][0];
        var style = bui[i][1];
        if (bc.test(text)) {
            while (bmatch = bc.exec(text)) {
                var text = text.replace(bmatch[0], style[0] + bmatch[1] + style[1]);
            }
        }
    }
    return text;
}

function markdownToHtml(text) {
    return text
            .replace(/\[b\]/gi, '<b>')
            .replace(/\[\/b\]/gi, '</b>')
            .replace(/\[i\]/gi, '<i>')
            .replace(/\[\/i\]/gi, '</i>')
            .replace(/\[u\]/gi, '<u>')
            .replace(/\[\/u\]/gi, '</u>');
}