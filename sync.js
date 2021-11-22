$(function () {
    $("#syncdata").click(() => {
        $.ajax({
            method: "POST",
            url: "sync.php"
        })
            .done(() => { });
    });
})