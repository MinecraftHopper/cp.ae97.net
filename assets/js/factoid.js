function loadData(game) {
    viewModel.tableLoaded(false);
    var posting = $.post('/factoid/get', {db: game}, "json");
    posting.done(parseData);
}

function parseData(data) {
    var json = JSON.parse(data);
    viewModel.gameTable.removeAll();
    viewModel.factoidTable.removeAll();
    viewModel.gameName(json.gamerequest.displayname);
    for (var counter = 0; counter < json.games.length; counter++) {
        var game = json.games[counter];
        viewModel.gameTable.push({'displayname': game.displayname, 'idname': game.idname});
    }

    for (var counter = 0; counter < json.factoids.length; counter++) {
        var item = json.factoids[counter];
        viewModel.factoidTable.push({
            'id': item.id,
            'name': item.name,
            'content': parseToText(item.content)
        });
    }
    viewModel.tableLoaded(true);
}

function loadDataFromMenu(data, event) {
    loadData(data.idname);
}
