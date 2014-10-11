function parseToText($string) {
    var autolinker = new Autolinker({
        email: false,
        twitter: false,
        stripPrefix: false
    });
    return mircToHtml(autolinker.link($string)).replace(';;', '<br>');
}

function mircToHtml(text) {
    //control codes
    var rex = /\003([0-9]{1,2})[,]?([0-9]{1,2})?([^\003]+)/, matches, colors;
    if (rex.test(text)) {
        while (cp = rex.exec(text)) {
            if (cp[2]) {
                var cbg = cp[2];
            }
            var text = text.replace(cp[0], '<span class="fg' + cp[1] + ' bg' + cbg + '">' + cp[3] + '</span>');
        }
    }
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
            bmatch = bc.exec(text);
            while (bmatch) {
                var text = text.replace(bmatch[0], style[0] + bmatch[1] + style[1]);
                bmatch = bc.exec(text);
            }
        }
    }
    return text;
}