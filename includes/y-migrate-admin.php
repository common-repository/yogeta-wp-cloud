<?php
include_once( __DIR__ . '/../functions.php' );
$type     = ycwp_check_zip_size() ? 'zipped' : 'obo';
$HAS_CURL = extension_loaded( 'curl' ) ? 1 : 0;

global $wpdb;
global $wp_version;
$level1 = false;
$level2 = false;
$level3 = false;
$host   = ycwp_get_hostname( get_site_url() );

$blocking = ycwp_check_blocking_plugins();
if ( isset( $_POST['rest_path'] ) ) {
	$rest_path      = sanitize_url($_POST['rest_path']);
	$wp_admin_url   = sanitize_url($_POST['wp_admin_url']);
	$wp_site_url    = sanitize_url($_POST['wp_site_url']);
	$main_dir_path  = sanitize_url($_POST['main_dir_path']);
	$wp_version     = sanitize_text_field($_POST['wp_version']);
	$migration_type = sanitize_text_field($_POST['migration_type']);
	$has_curl       = sanitize_text_field($_POST['has_curl']);
	$username       = sanitize_text_field($_POST['username']);
	$password       = sanitize_text_field($_POST['password']);
	$level1         = true;
}

if ( isset( $_POST['apikey'] ) ) {
	$level1 = false;
	$level2 = true;
	$level3 = true;
}
?>
<div class="container-fluid my-3 ycwp_wrapper" style="direction: ltr">
	<?php if ( ! $level3 ): ?>
        <div class="row justify-content-center mb-4">
            <div class="col-auto">
                <img src="<?php echo plugin_dir_url( __FILE__ ) ?>../assets/images/header.webp" class="img-fluid" alt="">
            </div>
        </div>
	<?php endif; ?>
	<?php
	if ( ! $level1 && ! $level2 ):
		?>
        <div class="row justify-content-center inner-container">
            <div class="col-auto">
                <div class="login-wrapper row justify-content-center">
                    <div class="col-auto text-center">
                        <h1>Enter your WordPress admin credentials</h1>
                    </div>
                    <div class="col">
                        <form action="" method="POST">
                            <input type="hidden" name="rest_path" id="rest_path" value="<?php ycwp_escape_and_sanitize(get_rest_url(),'url') ?>">
                            <input type="hidden" name="wp_admin_url" id="wp_admin_url" value="<?php ycwp_escape_and_sanitize(get_admin_url(),'url') ?>">
                            <input type="hidden" name="wp_site_url" id="wp_site_url" value="<?php ycwp_escape_and_sanitize(get_site_url(),'url') ?>">
                            <input type="hidden" name="main_dir_path" id="main_dir_path" value="<?php ycwp_escape_and_sanitize(get_home_path(),'url') ?>">
                            <input type="hidden" name="wp_prefix" id="wp_prefix" value="<?php  ycwp_escape_and_sanitize($wpdb->prefix) ?>">
                            <input type="hidden" name="wp_version" id="wp_version" value="<?php ycwp_escape_and_sanitize($wp_version) ?>">
                            <input type="hidden" name="migration_type" id="migration_type" value="<?php ycwp_escape_and_sanitize($type) ?>">
                            <input type="hidden" name="has_curl" id="migration_type" value="<?php ycwp_escape_and_sanitize($HAS_CURL) ?>">
                            <div class="row justify-content-center">
                                <div class="form-group col-9 mb-4">
                                    <label for="username">Username</label>
                                    <input type="text" id="username" name="username"
                                           placeholder="Your wordpress user name"
                                           class="form-control"/>
                                </div>
                                <div class="form-group col-9">
                                    <label for="password">Password</label>
                                    <input type="password" id="password" class="form-control" name="password"
                                           placeholder="Your wordpress password"/>
                                </div>
                                <!--                            <div class="_yogeta_error">You must identify with an admin account</div>-->
                            </div>
                            <div class="ymg_button_wrapper row justify-content-center">
                                <div class="col-6">
                                    <button type="submit" class="btn btn-primary form-control">Next</button>
                                </div>
                            </div>
                        </form>

                    </div>

                </div>
            </div>
        </div>
	<?php endif ?>

	<?php if ( $level1 && ! $level2 ) : ?>
		<?php if ( ! count( $blocking ) ): ?>
            <div class="row justify-content-center inner-container">
                <div class="col-auto">
                    <div class="login-wrapper row justify-content-center login-wrapper-level-2">
                        <div class="col-12 text-center">
                            <h1>Enter your migrate Yogeta API token</h1>
                        </div>
                        <div class="col">
                            <form action="" id="migrate-form" method="POST">
                                <input type="hidden" name="action" value="ycwp_migrate">
                                <input type="hidden" name="rest_path" id="rest_path" value="<?php ycwp_escape_and_sanitize(get_rest_url(),'url') ?>">
                                <input type="hidden" name="wp_admin_url" id="wp_admin_url"
                                       value="<?php  ycwp_escape_and_sanitize(get_admin_url(),'url') ?>">
                                <input type="hidden" name="wp_site_url" id="wp_site_url" value="<?php ycwp_escape_and_sanitize(get_site_url(),'url') ?>">
                                <input type="hidden" name="main_dir_path" id="main_dir_path"
                                       value="<?php ycwp_escape_and_sanitize(get_home_path(),'url') ?>">
                                <input type="hidden" name="wp_prefix" id="wp_prefix" value="<?php ycwp_escape_and_sanitize($wpdb->prefix) ?>">
                                <input type="hidden" name="wp_version" id="wp_version" value="<?php ycwp_escape_and_sanitize($wp_version) ?>">
                                <input type="hidden" name="migration_type" id="migration_type" value="<?php ycwp_escape_and_sanitize($type) ?>">
                                <input type="hidden" name="has_curl" id="has_curl" value="<?php ycwp_escape_and_sanitize($HAS_CURL) ?>">
                                <input type="hidden" name="username" id="username" value="<?php ycwp_escape_and_sanitize($username) ?>">
                                <input type="hidden" name="password" id="password" value="<?php ycwp_escape_and_sanitize($password) ?>">
                                <div class="row justify-content-center">
                                    <div class="form-group col-9 mb-4">
                                        <div class="row " id="ymg_text_area_wrapper">
                                            <div class="col-12">
                                                <label for="apikey">Paste your API token here:</label>
                                                <textarea id="apikey" name="apikey"
                                                          class="key-form form-control mb-3"></textarea>
                                                <a href="<?php ycwp_escape_and_sanitize(YCWP_API_KEY_PAGE,'url') ?>" target="_blank" class="where-can-i">Where can I find it?</a>
                                            </div>
                                        </div>
                                        <div class="row mt-2">
                                            <div class="col-12" id="valid-token" style="display: none">
                                                Your Token is valid! you can carry on with the migration process
                                            </div>
                                            <div class="col-12" id="invalid-token" style="display: none">
                                                Your Token is invalid! please check your token
                                            </div>
                                        </div>
                                    </div>
                                    <div class="_yogeta_error" id="yogeta_migrate_error" style="display: none">You must
                                        identify with an admin account
                                    </div>
                                    <div class="ymg_button_wrapper row justify-content-center">
                                        <div class="col-6">
                                            <button type="button" id="migrate-button"
                                                    class="btn btn-primary form-control"
                                                    disabled>Migrate
                                            </button>
                                        </div>

                                    </div>
                                    <div class="row justify-content-center" id="migrate_loading">
                                        <div class="col-12 text-center">
                                            <div class="loadingio-spinner-pulse-iv0trsbt1mk">
                                                <div class="ldio-k0uldxknes">
                                                    <div></div>
                                                    <div></div>
                                                    <div></div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-12 text-center">
                                            Preparing your migrate Process..
                                        </div>
                                    </div>

                                </div>

                            </form>

                        </div>

                    </div>
                </div>
            </div>
		<?php else : ?>
            <div class="row justify-content-center">
                <div class="col-5">
                    <div class="row">
                        <div class="col-12 mb-2">
                            <h1 class="mb-3" style="font-weight: bold">Warning!</h1>
                            <h3>those plugins can harm the migration process , please disable them before continuing the migration process.</h3>
                        </div>
                        <ul>
	                        <?php foreach ( $blocking as $blockingPlugin ): ?>
                            <li style="color:red">
	                            <?php ycwp_escape_and_sanitize($blockingPlugin['Name'])?>
                            </li>
	                        <?php endforeach; ?>
                        </ul>
                    </div>
                </div>

            </div>
		<?php endif; ?>
	<?php endif; ?>

	<?php if ( $level3 ) : ?>
        <div class="inner-container">
            <section class="container-fluid">
                <div class="progress-wrapper">
                    <div class="progress-inner-wrapper">
                        <div class="row">
                            <div class="col-lg-6">
                                <h1>
                                    Migrating to Yogeta
                                </h1>
                                <h2>
                                    Now we start working on your site, It may take a while. Don't worry we will update
                                    you when process is done.
                                </h2>
                                <div class="row justify-content-center">
                                    <div class="col-12 mb-3">
                                        <label class="checkbox-container">Notify me by email once the process is
                                            complete.
                                            <input type="checkbox">
                                            <span class="checkmark"></span>
                                        </label>
                                    </div>
                                    <div class="col-12 text-center">
                                        <div class="loadingio-spinner-pulse-iv0trsbt1mk">
                                            <div class="ldio-k0uldxknes">
                                                <div></div>
                                                <div></div>
                                                <div></div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-12 text-center">
                                        Migration In Progress...
                                    </div>
                                </div>
                                <!--                                <div class="progress-bar-wrapper">-->
                                <!--                                    <span>Copy files to the new server...</span>-->
                                <!--                                    <div class="progress">-->
                                <!--                                        <div class="progress-bar" id="progressBar"></div>-->
                                <!--                                    </div>-->
                                <!--                                    <label class="checkbox-container">Notify me by email once the process is complete.-->
                                <!--                                        <input type="checkbox">-->
                                <!--                                        <span class="checkmark"></span>-->
                                <!--                                    </label>-->
                                <!--                                </div>-->

                            </div>
                            <div class="col-lg-6 girl-image-container">
                                <img src="<?php ycwp_escape_and_sanitize(plugin_dir_url( __FILE__ ),'url') ?>../assets/images/girl.svg">
                            </div>
                        </div>
                    </div>
                </div>
            </section>
        </div>

	<?php endif; ?>
</div>


<script>
    jQuery(document).ready(function ($) {
        $('#apikey').on('input', ($event) => {
            let apiKey = $event.target.value;
            $('#valid-token').css({display: 'none'})
            $('#invalid-token').css({display: 'none'})
            $.post({
                url: '<?php ycwp_escape_and_sanitize(YCWP_API_SERVER,'url') ?>/migrate/validate-domain-apikey',
                data: JSON.stringify({
                    domain: '<?php ycwp_escape_and_sanitize($host,'url') ?>',
                    api_key: apiKey
                }),
                dataType: "json",
                contentType: 'application/json',
                success: (res) => {
                    console.log(res)
                    if (res.statusCode === 200) {
                        $('#valid-token').css({display: 'block'})
                        $('#migrate-button').prop('disabled', false)
                    }
                },
                error: (res) => {
                    $('#invalid-token').css({display: 'block'})
                    $('#migrate-button').prop('disabled', true)
                }
            })
        })
        $('#migrate-button').on('click', form => {
            form.preventDefault();
            $("#migrate_loading").css({display: 'block'})
            $("#ymg_text_area_wrapper").css({display: 'none'})

            $(".ymg_button_wrapper").css({display: 'none'})
            $('#valid-token').css({display: 'none'})
            let inputs = $('#migrate-form :input');
            let migrateError = null;
            $('#migrate-button').prop('disabled', true);
            $('#yogeta_migrate_error').css({display: 'none'});
            let values = {}
            inputs.each(function () {
                if (this.name !== '') {
                    switch (this.name) {
                        case 'has_curl': {
                            values[this.name] = !!parseInt($(this).val());
                            break;
                        }
                        default:
                            values[this.name] = $(this).val()
                    }

                }
            })

            $.post({
                url: ajaxurl,
                action: 'ycwp_handle_migrate',
                data: values,
                dataType: "json",
                success: (res => {
                    $.post(
                        {
                            url: '<?php ycwp_escape_and_sanitize(YCWP_API_SERVER,'url')?>' + '/migrate',
                            data: JSON.stringify(res.data),
                            dataType: "json",
                            contentType: 'application/json',
                            success: (success => {
                                if (success.statusCode === 200) {
                                    $('#migrate-form').submit();

                                } else {
                                    migrateError = true;
                                    $("#ymg_text_area_wrapper").css({display: 'block'})
                                    $("#migrate_loading").css({display: 'none'})
                                    $(".ymg_button_wrapper").css({display: 'block'})
                                    $('#yogeta_migrate_error').css({display: 'block'});
                                    $('#yogeta_migrate_error').text(success.message);
                                }
                            })
                        }
                    )
                })
            })

        })
    })
</script>