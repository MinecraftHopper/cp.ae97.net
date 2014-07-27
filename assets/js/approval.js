function approveUser(data) {
    var value = data.id;
    var posting = $.post('/cp/user/approve/' + value, {}, "json");
    posting.done(getUnapprovedUserList);
}

function deleteUser(data) {
    var value = data.id;
    console.info(value);
    var posting = $.post('/cp/user/delete/' + value, {}, "json");
    posting.done(getUnapprovedUserList);
}

function getUnapprovedUserList() {
    $("#unapprovedTable").hide();
    var posting = $.post('/cp/user/list/unapproved', {}, "json");
    posting.done(function(data) {
        var json = JSON.parse(data);
        viewModel.unapprovedUsers.removeAll();
        for (var counter = 0; counter < json.length; counter++) {
            var item = json[counter];
            viewModel.unapprovedUsers.push({
                'id': item.id,
                'email': item.email,
                'user': item.user
            });
        }
        $("#unapprovedTable").show();
    });
}