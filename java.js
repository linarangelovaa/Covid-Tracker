$(function () {
$('#search').keyup(function () {
    search_table($(this).val());
});
function search_table(value) {
    $('#countryTable tr').each(function () {
        var found = 'false';
        $(this).each(function () {
            if ($(this).text().toLowerCase().indexOf(value.toLowerCase()) >= 0) {
                found = 'true';
            }
        });
        if (found == 'true') {
            $(this).show();
            $('.tHead').show();
        } else {
            $(this).hide();
        }
    });
}
})

