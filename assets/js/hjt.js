function loadData(game) {
    viewModel.tableLoaded(false);
    var posting = $.post('/hjt/get', "", "json");
    posting.done(parseData);
}

function parseData(data) {
    var json = JSON.parse(data);
    viewModel.gameTable.removeAll();
    viewModel.factoidTable.removeAll();
    viewModel.gameName(json.gamerequest.displayname);
    for (var counter = 0; counter < json.factoids.length; counter++) {
        var item = json.hjt[counter];
        viewModel.factoidTable.push({
            'name': item.name,
            'content': parseToText(item.content)
        });
    }

    viewModel.tableLoaded(true);
}

function loadDataFromMenu(data, event) {
    loadData(data.idname);
}
