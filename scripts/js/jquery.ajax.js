jQuery(document).ready(function ($) {
    let inMigrate = false;
    let username = $('#username').val() || null;
    let password = $('#password').val() || null;
    let apikey = $('#apikey').val() || null;
    let rest_path = $('#rest_path').val() || null;
    let wp_admin_url = $('#wp_admin_url').val() || null;
    let wp_site_url = $('#wp_site_url').val() || null;
    let main_dir_path = $('#main_dir_path').val() || null;
    let migrate_type = $('#migrate_type').val() || null;
    $('#migrate-form button').attr('disabled', true)
    $('#migrate-form').on('change', (c => {
        username = $('#username').val() || null;
        password = $('#password').val() || null;
        apikey = $('#apikey').val() || null;
        rest_path = $('#rest_path').val() || null;
        wp_admin_url = $('#wp_admin_url').val() || null;
        wp_site_url = $('#wp_site_url').val() || null;
        main_dir_path = $('#main_dir_path').val() || null;
        migrate_type = $('#migrate_type').val() || null;
        // $('#migrate-form button').attr('disabled', !(username && password && apikey))
    }))
    // $(`#migrate-form`).submit(e => {
    //     e.preventDefault();
    //     $.post({
    //         url: 'http://localhost:3000/migrate',
    //         dataType: 'json',
    //         data: {
    //             action: "ymg_migrate",
    //             username,
    //             password,
    //             apikey,
    //             rest_path,
    //             wp_admin_url,
    //             wp_site_url,
    //             main_dir_path
    //         },
    //         success: async (res) => {
    //             console.log(res.data)
    //         }
    //     })
    // })

    function sendFile(path) {
        $.post({
            url: '/wp-admin/admin-ajax',
            dataType: 'json'
        })
    }
});