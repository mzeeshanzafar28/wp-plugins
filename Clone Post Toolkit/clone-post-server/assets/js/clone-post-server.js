jQuery(document).ready(function ($) {
    $('#wrapper').on('click', 'button[name="plus[]"]', function (e) {
        e.preventDefault();
        var x = $('#wrapper');
        var content = `
            <div>
                <label>Site URL</label>
                <input type="text" name="inp[]" style="width:25%;" placeholder="Enter Site URL">
                <button class="btn btn-info rounded-circle" name="plus[]" style="font-size:14px"><strong>+</strong></button>
                <button class="btn btn-secondary rounded-circle" name="minus[]" style="font-size:14px"><strong>-</strong></button>
            </div>`;
        $(x).append(content);
    });

    $('#wrapper').on('click', 'button[name="minus[]"]', function (e) {
        e.preventDefault();
        $(this).parent().remove();
    });
});
